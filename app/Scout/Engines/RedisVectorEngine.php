<?php

namespace App\Scout\Engines;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\LazyCollection;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use phpw2v\Word2Vec;
use Exception;
use Illuminate\Support\Facades\Log;

class RedisVectorEngine extends Engine
{
    protected $redis;
    protected $indexPrefix;
    protected $word2vec;
    protected $vectorDimensions = 300; // Standard Word2Vec dimension

    public function __construct($indexPrefix = 'scout:')
    {
        $this->redis = Redis::connection();
        $this->indexPrefix = $indexPrefix;
        $this->initializeWord2Vec();
    }

    /**
     * Initialize Word2Vec model (optional for vector search)
     */
    protected function initializeWord2Vec()
    {
        try {
            $this->word2vec = new Word2Vec();
            $modelPath = config('scout.word2vec.model_path', storage_path('app/GoogleNews-vectors-negative300.bin'));

            if (file_exists($modelPath . ".model")) {
                $this->word2vec->load($modelPath);
//                Log::info('Word2Vec model loaded successfully', ['path' => $modelPath]);
            } else {
//                Log::warning('Word2Vec model file not found', ['path' => $modelPath]);
                $this->word2vec = null;
            }
        } catch (Exception $e) {
//            Log::error('Failed to initialize Word2Vec', ['error' => $e->getMessage()]);
            $this->word2vec = null;
        }
    }

    public function update($models)
    {
        if ($models->isEmpty()) {
            return;
        }

        $models->each(function ($model) {
            $this->updateModel($model);
        });
    }

    protected function updateModel($model)
    {
        $searchableData = $model->toSearchableArray();
        $indexName = $this->getIndexName($model);
        $documentId = $this->getDocumentId($model);

        // Ensure the index exists
        $this->ensureIndexExists($model);

        // Prepare document for Redis Query Engine
        $document = array_merge($searchableData, [
            '__model_id' => $model->getScoutKey(),
            '__model_class' => get_class($model),
        ]);

        // Add vector if using vector search
        if (method_exists($model, 'toSearchableVector') || $this->shouldUseVectorSearch($model)) {
            $vector = $this->getModelVector($model);
            $document['__vector'] = $this->encodeVector($vector);
        }

        $flatDocument = $this->flattenDocument($document);

        // Store in Redis hash (will be automatically indexed by Query Engine)
        $this->redis->hset($indexName . ':' . $documentId, $flatDocument);
    }

    public function delete($models)
    {
        if ($models->isEmpty()) {
            return;
        }

        $models->each(function ($model) {
            $indexName = $this->getIndexName($model);
            $documentId = $this->getDocumentId($model);
            $this->redis->del($indexName . ':' . $documentId);
        });
    }

    public function search(Builder $builder)
    {
        $models = $this->searchModels($builder)->take($builder->limit);

        return [
            'results' => $models->all(),
            'total' => count($models),
        ];
    }

    public function paginate(Builder $builder, $perPage, $page)
    {
        $models = $this->searchModels($builder);

        return [
            'results' => $models->forPage($page, $perPage)->all(),
            'total' => count($models),
        ];
    }

    protected function searchModels(Builder $builder)
    {
        // Check if this is a vector search request
        if ($this->isVectorSearch($builder)) {
            return $this->performVectorSearch($builder);
        }

        // Use Redis Query Engine for text search
        if ($builder->query) {
            return $this->performQueryEngineSearch($builder);
        }

        // Fallback to database search for complex constraints
        return $this->performDatabaseSearch($builder);
    }

    /**
     * Determine if this should be a vector search
     */
    protected function isVectorSearch(Builder $builder)
    {
        return (property_exists($builder, 'vector') && $builder->vector) ||
            (property_exists($builder, 'useVector') && $builder->useVector) ||
            method_exists($builder->model, 'preferVectorSearch');
    }

    /**
     * Determine if model should use vector search
     */
    protected function shouldUseVectorSearch($model)
    {
        return method_exists($model, 'toSearchableVector') ||
            method_exists($model, 'preferVectorSearch');
    }

    /**
     * Perform search using Redis Query Engine
     */
    protected function performQueryEngineSearch(Builder $builder)
    {
        $indexName = $this->getIndexName($builder->model);
        $query = $this->buildQueryEngineQuery($builder);

        try {
            // Use FT.SEARCH command for full-text search
            $results = $this->redis->rawCommand('FT.SEARCH', $indexName, $query, 'LIMIT', 0, $builder->limit ?: 50);

            return $this->processQueryEngineResults($builder, $results);

        } catch (Exception $e) {
//            Log::error('Redis Query Engine search failed', [
//                'error' => $e->getMessage(),
//                'query' => $query,
//                'index' => $indexName
//            ]);

            // Fallback to database search
            return $this->performDatabaseSearch($builder);
        }
    }

    /**
     * Build query string for Redis Query Engine
     */
    protected function buildQueryEngineQuery(Builder $builder)
    {
        $searchTerm = $builder->query;
        $fuzzyEnabled = $this->shouldUseFuzzySearch($builder);

        // Handle different search patterns
        if (strlen($searchTerm) <= 2) {
            // For short queries like "J", use prefix matching
            return "{$searchTerm}*";
        }

        // Build the main query with fuzzy search support
        $query = $this->buildMainQuery($searchTerm, $builder, $fuzzyEnabled);

        // Add constraints from builder
        $constraints = $this->buildConstraints($builder);
        if ($constraints) {
            $query .= ' ' . $constraints;
        }

        return $query;
    }

    /**
     * Build the main search query with fuzzy support
     */
    protected function buildMainQuery($searchTerm, Builder $builder, $fuzzyEnabled)
    {
        // Add field-specific searches if model defines searchable fields
        if (method_exists($builder->model, 'getSearchableFields')) {
            $fields = $builder->model->getSearchableFields();
            $fieldQueries = [];

            foreach ($fields as $field) {
                if ($fuzzyEnabled) {
                    // Add both exact prefix match and fuzzy search
                    $fieldQueries[] = "@{$field}:({$searchTerm}*)";
                    $fieldQueries[] = "@{$field}:(%{$searchTerm}%)";
                } else {
                    $fieldQueries[] = "@{$field}:({$searchTerm}*)";
                }
            }

            if (!empty($fieldQueries)) {
                return '(' . implode(' | ', $fieldQueries) . ')';
            }
        }

        // Default query for all text fields
        if ($fuzzyEnabled) {
            // Combine prefix matching with fuzzy search
            return "({$searchTerm}*) | (%{$searchTerm}%)";
        }

        return "{$searchTerm}*";
    }

    /**
     * Determine if fuzzy search should be used
     */
    protected function shouldUseFuzzySearch(Builder $builder)
    {
        // Check if explicitly requested
        if (property_exists($builder, 'fuzzy') && $builder->fuzzy !== null) {
            return $builder->fuzzy;
        }

        // Check if model prefers fuzzy search
        if (method_exists($builder->model, 'preferFuzzySearch')) {
            return $builder->model->preferFuzzySearch();
        }

        // Enable fuzzy search by default for queries longer than 3 characters
        return strlen($builder->query) > 3;
    }

    /**
     * Build constraints for Redis Query Engine
     */
    protected function buildConstraints(Builder $builder)
    {
        $constraints = [];

        // Handle where clauses
        foreach ($builder->wheres as $key => $value) {
            if ($key === '__soft_deleted') {
                continue; // Handle separately
            }
            $constraints[] = "@{$key}:{$value}";
        }

        // Handle whereIn clauses
        foreach ($builder->whereIns as $key => $values) {
            $valueList = implode('|', array_map(function($v) { return addslashes($v); }, $values));
            $constraints[] = "@{$key}:({$valueList})";
        }

        return implode(' ', $constraints);
    }

    /**
     * Process results from Redis Query Engine
     */
    protected function processQueryEngineResults(Builder $builder, $results)
    {
        if (!is_array($results) || count($results) < 2) {
            return collect();
        }

        // First element is the count, rest are results
        $count = $results[0];
        $documents = array_slice($results, 1);

        $modelIds = [];

        // Process document results (every 2 elements: key, field-value pairs)
        for ($i = 0; $i < count($documents); $i += 2) {
            if (!isset($documents[$i + 1])) continue;

            $documentData = $documents[$i + 1];
            $fields = $this->parseDocumentFields($documentData);

            if (isset($fields['__model_id'])) {
                $modelIds[] = $fields['__model_id'];
            }
        }

        return $this->loadModelsFromIds($builder, $modelIds);
    }

    /**
     * Parse document fields from Redis Query Engine result
     */
    protected function parseDocumentFields(array $fieldData)
    {
        $fields = [];

        // Field data comes as [field1, value1, field2, value2, ...]
        for ($i = 0; $i < count($fieldData); $i += 2) {
            if (isset($fieldData[$i + 1])) {
                $fields[$fieldData[$i]] = $fieldData[$i + 1];
            }
        }

        return $fields;
    }

    /**
     * Perform vector search using Redis-stored data
     */
    protected function performVectorSearch(Builder $builder)
    {
        $searchVector = $this->getSearchVector($builder);

        if (!$searchVector) {
            return $this->performQueryEngineSearch($builder);
        }

        $indexName = $this->getIndexName($builder->model);
        $pattern = $indexName . ':*';
        $keys = $this->redis->keys($pattern);

        if (empty($keys)) {
            return collect();
        }

        $scoredResults = [];

        foreach ($keys as $key) {
            $document = $this->redis->hgetall($key);

            if (!$document || !isset($document['__vector'])) {
                continue;
            }

            $storedVector = $this->decodeVector($document['__vector']);
            $similarity = $this->calculateCosineSimilarity($searchVector, $storedVector);

            $scoredResults[] = (object) [
                'model_id' => $document['__model_id'],
                'model_class' => $document['__model_class'],
                'score' => $similarity,
                'document' => $document
            ];
        }

        usort($scoredResults, function($a, $b) {
            return $b->score <=> $a->score;
        });

        $modelIds = array_map(function($result) {
            return $result->model_id;
        }, $scoredResults);

        return $this->loadModelsFromIds($builder, $modelIds);
    }

    /**
     * Ensure Redis Query Engine index exists
     */
    protected function ensureIndexExists($model)
    {
        $indexName = $this->getIndexName($model);

        try {
            // Check if index already exists
            $this->redis->rawCommand('FT.INFO', $indexName);
            return; // Index exists
        } catch (Exception $e) {
            // Index doesn't exist, create it
        }

        $this->createQueryEngineIndex($model, $indexName);
    }

    /**
     * Create Redis Query Engine index
     */
    protected function createQueryEngineIndex($model, $indexName)
    {
        try {
            $searchableData = $model->toSearchableArray();
            $schema = [];

            // Build schema based on searchable data
            foreach ($searchableData as $field => $value) {
                if (is_string($value)) {
                    $schema[] = $field;
                    $schema[] = 'TEXT';
                    $schema[] = 'SORTABLE'; // Make fields sortable
                } elseif (is_numeric($value)) {
                    $schema[] = $field;
                    $schema[] = 'NUMERIC';
                    $schema[] = 'SORTABLE';
                }
            }

            // Add metadata fields
            $schema = array_merge($schema, [
                '__model_id', 'TEXT',
                '__model_class', 'TEXT'
            ]);

            // Add vector field if using vector search
            if ($this->shouldUseVectorSearch($model)) {
                $schema = array_merge($schema, [
                    '__vector', 'VECTOR', 'FLAT', '6',
                    'TYPE', 'FLOAT32',
                    'DIM', $this->vectorDimensions,
                    'DISTANCE_METRIC', 'COSINE'
                ]);
            }

            // Create the index
            $command = array_merge([
                'FT.CREATE',
                $indexName,
                'ON', 'HASH',
                'PREFIX', '1', $indexName . ':',
                'SCHEMA'
            ], $schema);

            $this->redis->rawCommand(...$command);

//            Log::info('Created Redis Query Engine index', [
//                'index' => $indexName,
//                'schema_fields' => count($schema) / 2
//            ]);

        } catch (Exception $e) {
//            Log::error('Failed to create Redis Query Engine index', [
//                'index' => $indexName,
//                'error' => $e->getMessage()
//            ]);
        }
    }

    /**
     * Perform traditional database search
     */
    protected function performDatabaseSearch(Builder $builder)
    {
        $query = $builder->model->query()
            ->when(! is_null($builder->callback), function ($query) use ($builder) {
                call_user_func($builder->callback, $query, $builder, $builder->query);
            })
            ->when(! $builder->callback && count($builder->wheres) > 0, function ($query) use ($builder) {
                foreach ($builder->wheres as $key => $value) {
                    if ($key !== '__soft_deleted') {
                        $query->where($key, $value);
                    }
                }
            })
            ->when(! $builder->callback && count($builder->whereIns) > 0, function ($query) use ($builder) {
                foreach ($builder->whereIns as $key => $values) {
                    $query->whereIn($key, $values);
                }
            })
            ->when(! $builder->callback && count($builder->whereNotIns) > 0, function ($query) use ($builder) {
                foreach ($builder->whereNotIns as $key => $values) {
                    $query->whereNotIn($key, $values);
                }
            });

        $models = $this->ensureSoftDeletesAreHandled($builder, $query)
            ->get()
            ->values();

        if (count($models) === 0) {
            return $models;
        }

        $models = $models->first()->makeSearchableUsing($models)->filter(function ($model) {
            return $model->shouldBeSearchable();
        })->values();

        return $this->applyOrdering($builder, $models);
    }

    /**
     * Load models from database by IDs while preserving order
     */
    protected function loadModelsFromIds(Builder $builder, array $modelIds)
    {
        if (empty($modelIds)) {
            return collect();
        }

        $query = $builder->model->query()
            ->whereIn($builder->model->getScoutKeyName(), $modelIds)
            ->when(! is_null($builder->callback), function ($query) use ($builder) {
                call_user_func($builder->callback, $query, $builder, $builder->query);
            })
            ->when(! $builder->callback && count($builder->wheres) > 0, function ($query) use ($builder) {
                foreach ($builder->wheres as $key => $value) {
                    if ($key !== '__soft_deleted') {
                        $query->where($key, $value);
                    }
                }
            });

        $models = $this->ensureSoftDeletesAreHandled($builder, $query)->get();

        // Preserve order from search results
        $modelsByKey = $models->keyBy($builder->model->getScoutKeyName());
        $orderedModels = collect();

        foreach ($modelIds as $id) {
            if ($modelsByKey->has($id)) {
                $orderedModels->push($modelsByKey->get($id));
            }
        }

        return $orderedModels;
    }

    protected function getModelVector($model)
    {
        if (method_exists($model, 'toSearchableVector')) {
            return $model->toSearchableVector();
        }

        $searchableText = collect($model->toSearchableArray())
            ->filter(fn($value) => is_string($value))
            ->implode(' ');

        return $this->textToVector($searchableText);
    }

    protected function getSearchVector(Builder $builder)
    {
        if (property_exists($builder, 'vector') && $builder->vector) {
            return $builder->vector;
        }

        if ($builder->query) {
            return $this->textToVector($builder->query);
        }

        return null;
    }

    /**
     * Convert text to vector using Word2Vec embeddings
     */
    protected function textToVector($text)
    {
        $text = strtolower(trim($text));
        if (empty($text)) {
            return array_fill(0, $this->vectorDimensions, 0.0);
        }

        if ($this->word2vec) {
            return $this->textToVectorWithWord2Vec($text);
        }

        return $this->textToVectorFallback($text);
    }

    /**
     * Convert text to vector using Word2Vec
     */
    protected function textToVectorWithWord2Vec($text)
    {
        $words = array_filter(
            array_map('trim', explode(' ', $text)),
            fn($word) => !empty($word) && preg_match('/^[a-zA-Z]+$/', $word)
        );

        if (empty($words)) {
            return array_fill(0, $this->vectorDimensions, 0.0);
        }

        $vectors = [];
        $validWords = 0;

        foreach ($words as $word) {
            try {
                $wordVector = $this->word2vec->getVector($word);
                if ($wordVector !== null && is_array($wordVector)) {
                    $vectors[] = $wordVector;
                    $validWords++;
                }
            } catch (Exception $e) {
                continue;
            }
        }

        if ($validWords === 0) {
            return array_fill(0, $this->vectorDimensions, 0.0);
        }

        $dimensions = count($vectors[0]);
        $avgVector = array_fill(0, $dimensions, 0.0);

        foreach ($vectors as $vector) {
            for ($i = 0; $i < $dimensions; $i++) {
                $avgVector[$i] += $vector[$i];
            }
        }

        for ($i = 0; $i < $dimensions; $i++) {
            $avgVector[$i] /= $validWords;
        }

        $norm = sqrt(array_sum(array_map(fn($x) => $x * $x, $avgVector)));
        if ($norm > 0) {
            $avgVector = array_map(fn($x) => $x / $norm, $avgVector);
        }

        return $avgVector;
    }

    /**
     * Fallback text to vector conversion
     */
    protected function textToVectorFallback($text)
    {
        $words = explode(' ', $text);
        $vector = array_fill(0, $this->vectorDimensions, 0.0);

        foreach ($words as $word) {
            $hash = crc32($word);
            for ($i = 0; $i < $this->vectorDimensions; $i++) {
                $vector[$i] += sin(($hash + $i) * 0.01) * 0.1;
            }
        }

        $norm = sqrt(array_sum(array_map(fn($x) => $x * $x, $vector)));
        if ($norm > 0) {
            $vector = array_map(fn($x) => $x / $norm, $vector);
        }

        return $vector;
    }

    protected function calculateCosineSimilarity(array $a, array $b)
    {
        if (count($a) !== count($b)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < count($a); $i++) {
            $dotProduct += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        $normA = sqrt($normA);
        $normB = sqrt($normB);

        if ($normA == 0.0 || $normB == 0.0) {
            return 0.0;
        }

        return $dotProduct / ($normA * $normB);
    }

    protected function decodeVector($encodedVector)
    {
        return array_values(unpack('f*', $encodedVector));
    }

    protected function applyOrdering(Builder $builder, $models)
    {
        if ($builder->orders) {
            foreach (array_reverse($builder->orders) as $order) {
                $models = $models->sortBy($order['column'], SORT_REGULAR, $order['direction'] === 'desc');
            }
        } else {
            $models = $models->sortByDesc($builder->model->getScoutKeyName());
        }

        return $models->values();
    }

    protected function ensureSoftDeletesAreHandled($builder, $query)
    {
        if (Arr::get($builder->wheres, '__soft_deleted') === 0) {
            return $query->withoutTrashed();
        } elseif (Arr::get($builder->wheres, '__soft_deleted') === 1) {
            return $query->onlyTrashed();
        } elseif (in_array(SoftDeletes::class, class_uses_recursive(get_class($builder->model))) &&
            config('scout.soft_delete', false)) {
            return $query->withTrashed();
        }

        return $query;
    }

    protected function getIndexName($model)
    {
        return $this->indexPrefix . $model->searchableAs();
    }

    protected function getDocumentId($model)
    {
        return $model->getScoutKey();
    }

    protected function encodeVector(array $vector)
    {
        return pack('f*', ...$vector);
    }

    protected function flattenDocument(array $document)
    {
        $flattened = [];

        foreach ($document as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $flattened[$key] = json_encode($value);
            } else {
                $flattened[$key] = (string) $value; // Ensure string for Redis Query Engine
            }
        }

        return $flattened;
    }

    // Scout Engine Interface Methods
    public function mapIds($results)
    {
        $results = array_values($results['results']);

        return count($results) > 0
            ? collect($results)->pluck($results[0]->getScoutKeyName())
            : collect();
    }

    public function map(Builder $builder, $results, $model)
    {
        $results = $results['results'];

        if (count($results) === 0) {
            return $model->newCollection();
        }

        $objectIds = collect($results)
            ->pluck($model->getScoutKeyName())
            ->values()
            ->all();

        $objectIdPositions = array_flip($objectIds);

        return $model->getScoutModelsByIds(
            $builder, $objectIds
        )->filter(function ($model) use ($objectIds) {
            return in_array($model->getScoutKey(), $objectIds);
        })->sortBy(function ($model) use ($objectIdPositions) {
            return $objectIdPositions[$model->getScoutKey()];
        })->values();
    }

    public function lazyMap(Builder $builder, $results, $model)
    {
        $results = $results['results'];

        if (count($results) === 0) {
            return LazyCollection::empty();
        }

        $objectIds = collect($results)
            ->pluck($model->getScoutKeyName())
            ->values()->all();

        $objectIdPositions = array_flip($objectIds);

        return $model->queryScoutModelsByIds(
            $builder, $objectIds
        )->cursor()->filter(function ($model) use ($objectIds) {
            return in_array($model->getScoutKey(), $objectIds);
        })->sortBy(function ($model) use ($objectIdPositions) {
            return $objectIdPositions[$model->getScoutKey()];
        })->values();
    }

    public function getTotalCount($results)
    {
        return $results['total'];
    }

    public function flush($model)
    {
        $indexName = $this->getIndexName($model);

        // Delete all documents for this model type
        $keys = $this->redis->keys($indexName . ':*');
        if (!empty($keys)) {
            $this->redis->del($keys);
        }

        // Drop the index
        try {
            $this->redis->rawCommand('FT.DROPINDEX', $indexName);
        } catch (Exception $e) {
            // Index might not exist
        }
    }

    public function createIndex($name, array $options = [])
    {
        // Index creation is handled automatically when models are updated
        return true;
    }

    public function deleteIndex($name)
    {
        try {
            $this->redis->rawCommand('FT.DROPINDEX', $name);
            return true;
        } catch (Exception $e) {
//            Log::error('Failed to delete Redis Query Engine index', [
//                'index' => $name,
//                'error' => $e->getMessage()
//            ]);
            return false;
        }
    }

    /**
     * Get search suggestions/autocomplete with fuzzy support
     */
    public function suggest($query, $model, $limit = 10, $fuzzy = true)
    {
        $indexName = $this->getIndexName($model);

        try {
            // Build suggestion query
            if ($fuzzy && strlen($query) > 2) {
                // Use fuzzy search for suggestions
                $searchQuery = "({$query}*) | (%{$query}%)";
            } else {
                // Use prefix search for short queries or when fuzzy disabled
                $searchQuery = "{$query}*";
            }

            $results = $this->redis->rawCommand('FT.SEARCH', $indexName, $searchQuery, 'LIMIT', 0, $limit);
            return $this->processQueryEngineResults(new Builder($model, $query), $results);
        } catch (Exception $e) {
//            Log::error('Redis Query Engine suggest failed', ['error' => $e->getMessage()]);
            return collect();
        }
    }

    /**
     * Advanced search with fuzzy matching and edit distance control
     */
    public function fuzzySearch($query, $model, $maxDistance = 1, $limit = 50)
    {
        $indexName = $this->getIndexName($model);

        try {
            // Use Levenshtein distance for fuzzy matching
            // Syntax: %searchterm% for fuzzy with default distance (1)
            // Or %%searchterm%% for distance 2, %%%searchterm%%% for distance 3
            $fuzzyPrefix = str_repeat('%', $maxDistance);
            $fuzzySuffix = str_repeat('%', $maxDistance);
            $searchQuery = "{$fuzzyPrefix}{$query}{$fuzzySuffix}";

            $results = $this->redis->rawCommand('FT.SEARCH', $indexName, $searchQuery, 'LIMIT', 0, $limit);
            return $this->processQueryEngineResults(new Builder($model, $query), $results);
        } catch (Exception $e) {
//            Log::error('Redis Query Engine fuzzy search failed', ['error' => $e->getMessage()]);
            return collect();
        }
    }

    /**
     * Spellcheck and suggestion using Redis Query Engine
     */
    public function spellcheck($query, $model)
    {
        $indexName = $this->getIndexName($model);

        try {
            // Use FT.SPELLCHECK for spell correction suggestions
            $results = $this->redis->rawCommand('FT.SPELLCHECK', $indexName, $query);

            $suggestions = [];
            if (is_array($results)) {
                foreach ($results as $termResult) {
                    if (is_array($termResult) && count($termResult) >= 3) {
                        $term = $termResult[1];
                        $termSuggestions = $termResult[2];

                        foreach ($termSuggestions as $suggestion) {
                            if (is_array($suggestion) && count($suggestion) >= 2) {
                                $suggestions[] = [
                                    'original' => $term,
                                    'suggestion' => $suggestion[1],
                                    'score' => $suggestion[0] ?? 0
                                ];
                            }
                        }
                    }
                }
            }

            return collect($suggestions);
        } catch (Exception $e) {
//            Log::error('Redis Query Engine spellcheck failed', ['error' => $e->getMessage()]);
            return collect();
        }
    }

    /**
     * Check if Redis Query Engine is available
     */
    public function isQueryEngineAvailable()
    {
        try {
            $this->redis->rawCommand('FT.INFO', 'test');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

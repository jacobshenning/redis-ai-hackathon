<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class SearchUserController extends Controller
{
    protected $redis;
    protected $prefix = 'user:';
    protected $tokenPrefix = 'token:';

    public function __construct()
    {
        $this->redis = Redis::connection();
    }

    /**
     * Break a name into lowercase bigrams.
     */
    protected function tokenize($string)
    {
        $string = mb_strtolower($string);
        $tokens = [];

        for ($i = 0; $i < mb_strlen($string) - 1; $i++) {
            $tokens[] = mb_substr($string, $i, 2);
        }

        return array_unique($tokens);
    }

    /**
     * Store all users in Redis with token sets.
     */
    public function indexUsers()
    {
        $users = User::all();

        // Clear old keys
        $oldKeys = array_merge(
            $this->redis->keys($this->prefix . '*'),
            $this->redis->keys($this->tokenPrefix . '*')
        );
        if (!empty($oldKeys)) {
            $this->redis->del($oldKeys);
        }

        foreach ($users as $user) {
            $id = $user->id;
            $name = $user->name ?? '';

            // Store user data
            $this->redis->set($this->prefix . $id, json_encode([
                'id' => $id,
                'name' => $name
            ], JSON_UNESCAPED_UNICODE));

            // Store tokens pointing to user IDs
            foreach ($this->tokenize($name) as $token) {
                $this->redis->sadd($this->tokenPrefix . $token, $id);
            }
        }

        return response()->json([
            'message' => 'Users indexed with bigram search tokens',
            'count' => $users->count()
        ]);
    }

    /**
     * Search users by partial match.
     */
    public function search(Request $request)
    {
        $query = trim($request->input('query'));
        if ($query === '') {
            return response()->json(['users' => [], 'message' => 'Query required'], 400);
        }

        $tokens = $this->tokenize($query);
        if (empty($tokens)) {
            return response()->json(['users' => [], 'total' => 0, 'query' => $query]);
        }

        // Intersect token sets
        $setKeys = array_map(fn($t) => $this->tokenPrefix . $t, $tokens);
        $matchingIds = count($setKeys) > 1
            ? call_user_func_array([$this->redis, 'sinter'], $setKeys)
            : $this->redis->smembers($setKeys[0]);

        // Fetch full user records
        $users = [];
        foreach ($matchingIds as $id) {
            $json = $this->redis->get($this->prefix . $id);
            if ($json) {
                $users[] = json_decode($json, true);
            }
        }

        return response()->json([
            'users' => $users,
            'total' => count($users),
            'query' => $query
        ]);
    }
}

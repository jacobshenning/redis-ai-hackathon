import { type SharedData } from '@/types';
import { Head, Link, usePage, router } from '@inertiajs/react';
import { useState } from 'react';
import backgroundImage from '../../assets/background.png';

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;
    const [isLoading, setIsLoading] = useState(false);
    const [isJoining, setIsJoining] = useState(false);
    const [gameCode, setGameCode] = useState('');
    const [playerName, setPlayerName] = useState('');
    const [hostName, setHostName] = useState('');
    const [activeTab, setActiveTab] = useState('create'); // 'create' or 'join'

    const appName = import.meta.env.VITE_APP_NAME || 'Redis AI Hackathon';

    const handleStartGame = () => {
        if (!hostName.trim()) {
            alert('Please enter your player name.');
            return;
        }

        setIsLoading(true);

        router.post('/game/start', {
            gameType: 'default',
            hostName: hostName.trim(),
        }, {
            onSuccess: (page) => {
                // The backend will handle the redirect via Inertia::render()
            },
            onError: (errors) => {
                console.error('Error starting game:', errors);
                const message = errors.message || 'Failed to start game. Please try again.';
                alert(message);
                setIsLoading(false);
            },
            onFinish: () => {
                // This runs after the request completes
            }
        });
    };

    const handleJoinGame = () => {
        if (!gameCode.trim() || !playerName.trim()) {
            alert('Please enter both game code and player name.');
            return;
        }

        setIsJoining(true);

        router.post(`/game/${gameCode}/join`, {
            playerName: playerName.trim(),
        }, {
            onSuccess: (page) => {
                // The backend will handle the redirect via Inertia::render()
            },
            onError: (errors) => {
                console.error('Error joining game:', errors);
                const message = errors.message || 'Failed to join game. Please check the game code and try again.';
                alert(message);
                setIsJoining(false);
            },
            onFinish: () => {
                // This runs after the request completes
            }
        });
    };

    return (
        <>
            <Head title="Welcome">
            </Head>
            <main className="bg-cover">
                <div className="bg-cover bg-center fixed w-full h-full left-0 right-0"
                     style={{ backgroundImage: `url(${backgroundImage}` }}>
                    <div className="flex fixed py-16 w-full h-full justify-center items-center"
                         style={{ backgroundColor: 'rgba(0,0,0,0.2)' }}>

                        <div className="bg-sky-400 bg-opacity-80 border-4 border-sky-900 max-w-md w-full mx-4">
                            <div className="bg-sky-900 p-6 w-full">
                                <h1 className="text-3xl font-black text-white tracking-wider text-red-900">{appName}</h1>
                            </div>

                            <div className="p-8">

                            {/* Tab Buttons */}
                            <div className="flex mb-6 border-2 border-red-600 rounded overflow-hidden">
                                <button
                                    className={`flex-1 py-3 px-4 font-mono text-sm font-bold transition-colors ${
                                        activeTab === 'create'
                                            ? 'bg-red-600 text-white'
                                            : 'bg-red-900 text-red-300 hover:bg-red-700 cursor-pointer'
                                    }`}
                                    onClick={() => setActiveTab('create')}
                                >
                                    CREATE GAME
                                </button>
                                <button
                                    className={`flex-1 py-3 px-4 font-mono text-sm font-bold transition-colors border-l-2 border-red-600 ${
                                        activeTab === 'join'
                                            ? 'bg-red-600 text-white'
                                            : 'bg-red-900 text-red-300 hover:bg-red-700 cursor-pointer'
                                    }`}
                                    onClick={() => setActiveTab('join')}
                                >
                                    JOIN GAME
                                </button>
                            </div>

                            {/* Create Game Form */}
                            {activeTab === 'create' && (
                                <div className="space-y-4">
                                    <input
                                        id="hostName"
                                        className="text-center w-full py-4 text-xl bg-red-900 text-red-100 border-2 border-red-600 font-mono rounded"
                                        type="text"
                                        placeholder="Host Name"
                                        maxLength={20}
                                        value={hostName}
                                        onChange={(e) => setHostName(e.target.value)}
                                        disabled={isLoading}
                                    />
                                    <input
                                        className={`text-xl text-white w-full py-4 cursor-pointer transition-colors font-mono border-2 rounded ${
                                            isLoading || !hostName.trim()
                                                ? 'bg-gray-600 border-gray-700 cursor-not-allowed'
                                                : 'bg-red-600 border-red-800 hover:bg-red-500'
                                        }`}
                                        type="button"
                                        value={isLoading ? "Creating Game..." : "Start Game"}
                                        onClick={handleStartGame}
                                        disabled={isLoading || !hostName.trim()}
                                    />
                                </div>
                            )}

                            {/* Join Game Form */}
                            {activeTab === 'join' && (
                                <div className="space-y-4">
                                    <input
                                        id="code"
                                        className="text-center w-full py-4 text-xl bg-red-900 text-red-100 border-2 border-red-600 font-mono uppercase rounded"
                                        type="text"
                                        placeholder="Enter Code"
                                        maxLength={6}
                                        value={gameCode}
                                        onChange={(e) => setGameCode(e.target.value.toUpperCase())}
                                        disabled={isJoining}
                                    />
                                    <input
                                        id="playerName"
                                        className="text-center w-full py-4 text-xl bg-red-900 text-red-100 border-2 border-red-600 font-mono rounded"
                                        type="text"
                                        placeholder="Destroyer"
                                        maxLength={20}
                                        value={playerName}
                                        onChange={(e) => setPlayerName(e.target.value)}
                                        disabled={isJoining}
                                    />
                                    <input
                                        className={`text-xl text-white w-full py-4 cursor-pointer transition-colors font-mono border-2 rounded ${
                                            isJoining || !gameCode.trim() || !playerName.trim()
                                                ? 'bg-gray-600 border-gray-700 cursor-not-allowed'
                                                : 'bg-red-600 border-red-800 hover:bg-red-500'
                                        }`}
                                        type="button"
                                        value={isJoining ? "Joining..." : "Join Game"}
                                        onClick={handleJoinGame}
                                        disabled={isJoining || !gameCode.trim() || !playerName.trim()}
                                    />
                                </div>
                            )}


                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </>
    );
}

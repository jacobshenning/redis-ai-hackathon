import { type SharedData } from '@/types';
import { Head, usePage, router } from '@inertiajs/react';
import React, { useEffect, useState, useRef } from 'react';
import { useEchoPresence } from '@laravel/echo-react';
import CharacterCards from '@/components/character-cards';
import EquipmentCards from '@/components/equipment-cards';
import squares from '../../assets/autumn.svg';

interface GamePageProps extends SharedData {
    playerLocation: string,
    character?: {
        Name: string,
        Description: string
    },
    equipment?: {
        name: string,
        description: string,
        origin: string
    },
    gameCode: string,
    user: {
        id: number;
        name: string;
    };
    startCharacters: [],
    startEquipment: [],
    content: string,
}

export default function Game() {
    const appName = import.meta.env.VITE_APP_NAME || 'Redis AI Hackathon';
    const {
        playerLocation,
        user,
        gameCode,
        startCharacters,
        content,
        character,
        equipment,
        startEquipment
    } = usePage<GamePageProps>().props;

    console.log(squares);

    // Ref for the scrollable content div
    const contentScrollRef = useRef<HTMLDivElement>(null);

    // Game State
    const [connectedUsers, setConnectedUsers] = useState<Array<{ id: number; name: string }>>([]);
    const [message, setMessage] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [gameContent, setGameContent] = useState(content);
    const [delayedContent, setDelayedContent] = useState(content);
    const [startingCharacters, setStartingCharacters] = useState(startCharacters);
    const [startingEquipment, setStartingEquipment] = useState(startEquipment);
    const [locationId, setLocationId] = useState(playerLocation);

    // Broadcasting channels
    const { channel: gameChannel } = useEchoPresence(`games.${gameCode}`);
    const { channel: userChannel } = useEchoPresence(`users.${user.id}`);
    const { channel: locationChannel } = useEchoPresence(`games.${gameCode}.${locationId}`, [],() => null,[gameCode, locationId]);

    // Track if user was at bottom before content changes
    const wasAtBottomRef = useRef(true);

    // Helper function to check if user is scrolled to bottom
    const isScrolledToBottom = () => {
        if (!contentScrollRef.current) return false;

        const { scrollTop, scrollHeight, clientHeight } = contentScrollRef.current;
        // Allow for small margin (5px) to account for fractional pixels
        return scrollTop + clientHeight >= scrollHeight - 5;
    };

    // Track scroll position to know when user is at bottom
    const handleScroll = () => {
        wasAtBottomRef.current = isScrolledToBottom();
    };

    // Auto-scroll effect when delayedContent changes
    useEffect(() => {
        if (!contentScrollRef.current) return;

        // Use requestAnimationFrame to ensure DOM has updated
        requestAnimationFrame(() => {
            if (wasAtBottomRef.current && contentScrollRef.current) {
                contentScrollRef.current.scrollTop = contentScrollRef.current.scrollHeight;
            }
        });
    }, [delayedContent]);

    // Player channel listeners
    useEffect(() => {
        if (!userChannel) return;

        const channel = userChannel();

        channel.listen('.ChangedLocationEvent', (event) => {
            setLocationId(event.locationID);
            if (event.message) {
                console.log(event.message);
            }
        })
    });

    // Game channel listeners
    useEffect(() => {
        if (! gameChannel) return;

        const channel = gameChannel();

        channel.here((users) => {
            setConnectedUsers(users);
        });

        channel.listen('.GameDataBroadcastEvent', (event) => {
            setGameContent(gameContent + event.data);
        });

        channel.listen('.StartingCharactersEvent', (event) => {
            setStartingCharacters(event);
        });

        channel.listen('.StartingEquipmentEvent', (event) => {
            setStartingEquipment(event);
        });

        channel.joining((user) => {
            console.log('User joining:', user);
            setConnectedUsers(prev => {
                // Check if user already exists to prevent duplicates
                if (prev.some(u => u.id === user.id)) {
                    return prev;
                }
                return [...prev, user];
            });
        });

        channel.leaving((user) => {
            console.log('User leaving:', user);
            setConnectedUsers(prev => prev.filter(u => u.id !== user.id));
        });
    }, [gameChannel]);

    // Location channel listeners
    useEffect(() => {
        if (! locationChannel) return;

        const channel = locationChannel();

        channel.listen('.ArriveAtLocationEvent', (event) => {
            console.log(event);
            setGameContent(gameContent + " \n" + event.description);
        });
    }, [locationChannel, locationId]);

    // Typing Effect
    useEffect(() => {
        if (delayedContent === gameContent) return;

        const timeout = setTimeout(() => {
            const nextChar = gameContent.slice(0, delayedContent.length + 1);
            setDelayedContent(nextChar);
        }, 20); // 20 milliseconds delay

        return () => clearTimeout(timeout); // Clean up on re-render
    }, [gameContent, delayedContent]);

    const handleSubmit = () => {
        if (!message.trim() || isSubmitting) return;

        setIsSubmitting(true);
        router.post(`/game/${gameCode}/narrate`, {
            action: message.trim()
        }, {
            onSuccess: () => {
                setMessage('');
            },
            onFinish: () => {
                setIsSubmitting(false);
            }
        });
    };

    const handleCharacterSelection = async (character) => {
        router.post(`/game/${gameCode}/pickCharacter`, {
            name: character.Name
        }, {
            onSuccess: () => {
                setMessage('');
            },
            onFinish: () => {
                setIsSubmitting(false);
            }
        });
    };

    const handleEquipmentSelection = async (equipment) => {
        router.post(`/game/${gameCode}/pickEquipment`, {
            name: equipment.name
        }, {
            onSuccess: () => {
                setMessage('');
            },
            onFinish: () => {
                setIsSubmitting(false);
            }
        });
    };

    const handleKeyPress = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSubmit();
        }
    };

    return (
        <>
            <Head title="Game" />
            <main className="overflow-hidden">
                <header className="bg-red-900 text-white shadow-md p-4 h-16 flex items-center fixed w-full top-0 z-10">
                    <h1 className="text-2xl font-bold">{appName}</h1>
                    <div className="ml-auto flex items-center gap-2">
                        <span className="w-3 h-3 rounded-full bg-green-400"></span>
                        <span className="text-sm font-bold">{connectedUsers.length} online</span>
                    </div>
                </header>

                <div id="gameContent" className="flex py-16 bg-red-50 h-screen overflow-hidden">
                    <div ref={contentScrollRef} className="p-4 overflow-y-scroll basis-4/5" onScroll={handleScroll}>
                        {delayedContent ?
                            delayedContent.split('\n').map((piece, index) => (
                                <div key={index}>
                                    <p>{piece}</p>
                                    <br/>
                                </div>
                            )) :
                            <div></div>
                        }

                        { !character ? (
                            <CharacterCards
                                startingCharacters={startingCharacters}
                                onCharacterClick={handleCharacterSelection}
                            />
                        ) : (<div></div>)}

                        { !equipment && character ? (
                            <EquipmentCards
                                startingCharacters={startingEquipment}
                                onCharacterClick={handleEquipmentSelection}
                            />
                        ) : (<div></div>)}

                    </div>

                    <div className="w-80 bg-sky-900 p-4 text-white bg-repeat basis-1/5" style={{backgroundImage: `url(${squares})`}}>
                        <h3 className="font-black text-lg mb-4">Your Character:</h3>
                        <div className="space-y-2">
                            <h4 className="font-semibold capitalize">{character?.Name}</h4>
                            <p>{character?.Description}</p>
                        </div>
                        <h3 className="font-black text-lg mb-4 pt-8">Your Loot:</h3>
                        <div className="space-y-2">
                            <h4 className="font-semibold capitalize">{equipment?.name}</h4>
                            <p>{equipment?.description}</p>
                        </div>
                    </div>
                </div>

                <div
                    className="fixed bg-sky-900 h-16 w-full left-0 bottom-0 flex items-center py-2 border-t border-sky-800 justify-between">
                    <div className="basis-4/5 p-2 flex items-center">
                        <textarea
                            name="message"
                            id="message"
                            value={message}
                            onChange={(e) => setMessage(e.target.value)}
                            onKeyPress={handleKeyPress}
                            className="h-12 border-gray-300 w-full border p-2 resize-none bg-red-50"
                            placeholder="Type your message..."
                            disabled={isSubmitting}
                        />
                    </div>
                    <div className="basis-1/5 p-2 flex items-center">
                        <button
                            onClick={handleSubmit}
                            disabled={isSubmitting || !message.trim() || !locationId}
                            className="bg-red-900 border-red-600 hover:bg-red-500 border-2 text-white h-12 w-full cursor-pointer disabled:border-gray-500 disabled:bg-gray-400 disabled:cursor-not-allowed"
                        >
                            {isSubmitting ? 'Sending...' : 'Send'}
                        </button>
                    </div>
                </div>
            </main>
        </>
    );
}

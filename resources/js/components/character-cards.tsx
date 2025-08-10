import React from 'react';
import background from '../../assets/lisbon.svg'

export default function CharacterCards({ startingCharacters = [], onCharacterClick }) {
    const handleCharacterClick = async (character) => {
        try {
            await onCharacterClick(character);
        } catch (error) {
            console.error('Error sending character selection:', error);
        }
    };

    if (!startingCharacters.length) {
        return (
            <div> </div>
        );
    }

    return (
        <div>
            <h2 className="text-xl font-semibold text-gray-800 mb-2">Choose your Character</h2>
            <div className="grid grid-cols-1  md:grid-cols-2 lg:grid-cols-3 gap-4">
                {startingCharacters.map((character) => (
                    <div
                        key={character.Name}
                        onClick={() => handleCharacterClick(character)}
                        className="bg-red-900 bg-repeat bg-center border-red-600 border-2 text-white shadow-md hover:bg-red-500 transition-shadow duration-200 cursor-pointer p-4"
                        style={{ backgroundImage: `url(${background})` }}
                    >
                        <h3 className="text-lg font-semibold mb-2 capitalize">
                            {character.Name}
                        </h3>
                        <p className="text-sm text-white line-clamp-3">
                            {character.Description}
                        </p>
                    </div>
                ))}
            </div>
        </div>
)
    ;
};




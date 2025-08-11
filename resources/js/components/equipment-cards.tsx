import React from 'react';
import background from '../../assets/lisbon.svg'

export default function EquipmentCards({ startingEquipment: startingEquipment = [], onEquipmentClick: onEquipmentClick }) {
    const handleEquipmentClick = async (equipment) => {
        try {
            await onEquipmentClick(equipment);
        } catch (error) {
            console.error('Error sending character selection:', error);
        }
    };

    if (!startingEquipment || !startingEquipment.length) {
        return (
            <div> </div>
        );
    }

    return (
        <div>
            <h2 className="text-xl font-semibold text-gray-800 mb-2">Choose Your Weapon</h2>
            <div className="grid grid-cols-1  md:grid-cols-2 lg:grid-cols-3 gap-4">
                {startingEquipment.map((equipment) => (
                    <div
                        key={equipment.name}
                        onClick={() => handleEquipmentClick(equipment)}
                        style={{ backgroundImage: `url(${background})`}}
                        className="bg-red-900 bg-repeat bg-center border-red-600 border-2 text-white shadow-md hover:bg-red-500 transition-shadow duration-200 cursor-pointer p-4"
                    >
                        <h3 className="text-lg font-semibold mb-2 capitalize">
                            {equipment.name}
                        </h3>
                        <p className="text-sm text-white line-clamp-3">
                            {equipment.description + ' ' + equipment.origin}
                        </p>
                    </div>
                ))}
            </div>
        </div>
    );
};




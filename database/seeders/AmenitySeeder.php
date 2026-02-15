<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AmenitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $amenities = [
            // General
            '24/7 Access',
            'WiFi',
            'Parking',
            'Lockers',
            'Showers',
            'Sauna',
            'Steam Room',
            'Towel Service',
            'Juice Bar',
            'Pro Shop',
            'Lounge Area',
            'Childcare',

            // Equipment
            'Free Weights',
            'Machine Weights',
            'Cardio Zone',
            'Functional Training Area',
            'Powerlifting Platforms',
            'Squat Racks',
            'Smith Machines',
            'Kettlebells',
            'Resistance Bands',
            'Medicine Balls',
            'Plyometric Boxes',
            'TRX Suspension Training',

            // Specialized Areas
            'CrossFit Box',
            'Yoga Studio',
            'Pilates Studio',
            'Spin Studio',
            'Boxing Ring',
            'MMA Cage',
            'Heavy Bags',
            'Speed Bags',
            'Climbing Wall',
            'Swimming Pool',
            'Lap Pool',
            'Jacuzzi',
            'Cold Plunge',
            'Basketball Court',
            'Tennis Court',
            'Racquetball Court',
            'Squash Court',
            'Indoor Track',
            'Outdoor Training Area',

            // Services
            'Personal Training',
            'Group Classes',
            'Nutrition Counseling',
            'Physiotherapy',
            'Massage Therapy',
            'Body Composition Analysis',
            'Virtual Classes',
            'App Access',
        ];

        foreach ($amenities as $amenity) {
            \App\Models\Amenity::firstOrCreate(['name' => $amenity]);
        }
    }
}

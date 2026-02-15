<?php

namespace Database\Seeders;

use App\Models\WorkoutType;
use Illuminate\Database\Seeder;

class WorkoutTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $workoutTypes = [
            [
                'name' => 'Strength Training',
                'muscle_groups' => ['Chest', 'Back', 'Legs', 'Shoulders', 'Biceps', 'Triceps', 'Core', 'Full Body'],
            ],
            [
                'name' => 'Cardio',
                'muscle_groups' => ['Cardiovascular System', 'Legs', 'Full Body'],
            ],
            [
                'name' => 'Yoga',
                'muscle_groups' => ['Full Body', 'Core', 'Flexibility', 'Balance'],
            ],
            [
                'name' => 'Pilates',
                'muscle_groups' => ['Core', 'Full Body', 'Lower Body'],
            ],
            [
                'name' => 'HIIT',
                'muscle_groups' => ['Full Body', 'Cardiovascular System', 'Legs'],
            ],
            [
                'name' => 'CrossFit',
                'muscle_groups' => ['Full Body', 'Strength', 'Endurance'],
            ],
            [
                'name' => 'Calisthenics',
                'muscle_groups' => ['Upper Body', 'Core', 'Back', 'Chest', 'Arms'],
            ],
            [
                'name' => 'Powerlifting',
                'muscle_groups' => ['Legs', 'Back', 'Chest', 'Full Body'],
            ],
            [
                'name' => 'Stretching',
                'muscle_groups' => ['Full Body', 'Flexibility'],
            ],
            [
                'name' => 'Boxing',
                'muscle_groups' => ['Arms', 'Shoulders', 'Core', 'Cardiovascular System'],
            ],
            [
                'name' => 'Bodybuilding',
                'muscle_groups' => ['Chest', 'Back', 'Shoulders', 'Legs', 'Arms', 'Abs'],
            ],
            [
                'name' => 'Olympic Weightlifting',
                'muscle_groups' => ['Full Body', 'Legs', 'Shoulders', 'Back', 'Core'],
            ],
        ];

        foreach ($workoutTypes as $type) {
            $workoutType = WorkoutType::firstOrCreate(
                ['name' => $type['name']]
            );

            $metadata = [];
            foreach ($type['muscle_groups'] as $muscleGroup) {
                $metadata[] = [
                    'key' => 'muscle_group',
                    'value' => $muscleGroup,
                ];
            }

            $workoutType->updateMetadata($metadata);
        }
    }
}

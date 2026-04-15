<?php

namespace Database\Seeders;

use App\Models\Staff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $staffs = [

            /*
            |--------------------------------------------------------------------------
            | ADMINS (2)
            |--------------------------------------------------------------------------
            */
            [
                'role' => 'admin',
                'users' => [
                    [
                        'staff_id'  => 'TC' . now()->format('ym') . '0001',
                        'firstname' => 'Olugbenga',
                        'surname'   => 'Raymond',
                        'email'     => 'tutorialcenter001@gmail.com',
                        'tel'       => '08030000001',
                        'gender'    => 'male',
                    ],
                    // [
                    //     'staff_id'  => 'TC' . now()->format('ym') . '0002',
                    //     'firstname' => 'Jane',
                    //     'surname'   => 'Smith',
                    //     'email'     => 'admin2@tutorialcenter.com',
                    //     'tel'       => '08030000002',
                    //     'gender'    => 'female',
                    // ],
                ]
            ],

            /*
            |--------------------------------------------------------------------------
            | TUTORS (3)
            |--------------------------------------------------------------------------
            */
            // [
            //     'role' => 'tutor',
            //     'users' => [
            //         [
            //             'staff_id'  => 'TC' . now()->format('ym') . '0003',
            //             'firstname' => 'Michael',
            //             'surname'   => 'Brown',
            //             'email'     => 'tutor1@tutorialcenter.com',
            //             'tel'       => '08030000003',
            //             'gender'    => 'male',
            //         ],
            //         [
            //             'staff_id'  => 'TC' . now()->format('ym') . '0004',
            //             'firstname' => 'Sarah',
            //             'surname'   => 'Johnson',
            //             'email'     => 'tutor2@tutorialcenter.com',
            //             'tel'       => '08030000004',
            //             'gender'    => 'female',
            //         ],
            //         [
            //             'staff_id'  => 'TC' . now()->format('ym') . '0005',
            //             'firstname' => 'David',
            //             'surname'   => 'Wilson',
            //             'email'     => 'tutor3@tutorialcenter.com',
            //             'tel'       => '08030000005',
            //             'gender'    => 'male',
            //         ],
            //     ]
            // ],

            /*
            |--------------------------------------------------------------------------
            | ADVISORS (3)
            |--------------------------------------------------------------------------
            */
            // [
            //     'role' => 'advisor',
            //     'users' => [
            //         [
            //             'staff_id'  => 'TC' . now()->format('ym') . '0006',
            //             'firstname' => 'Grace',
            //             'surname'   => 'Taylor',
            //             'email'     => 'advisor1@tutorialcenter.com',
            //             'tel'       => '08030000006',
            //             'gender'    => 'female',
            //         ],
            //         [
            //             'staff_id'  => 'TC' . now()->format('ym') . '0007',
            //             'firstname' => 'Daniel',
            //             'surname'   => 'Anderson',
            //             'email'     => 'advisor2@tutorialcenter.com',
            //             'tel'       => '08030000007',
            //             'gender'    => 'male',
            //         ],
            //         [
            //             'staff_id'  => 'TC' . now()->format('ym') . '0008',
            //             'firstname' => 'Emily',
            //             'surname'   => 'Thomas',
            //             'email'     => 'advisor3@tutorialcenter.com',
            //             'tel'       => '08030000008',
            //             'gender'    => 'female',
            //         ],
            //     ]
            // ],
        ];

        foreach ($staffs as $group) {
            foreach ($group['users'] as $user) {

                Staff::create([
                    'staff_id'          => $user['staff_id'],
                    'firstname'         => $user['firstname'],
                    'middlename'        => null,
                    'surname'           => $user['surname'],
                    'email'             => $user['email'],
                    'tel'               => $user['tel'],
                    'password'          => Hash::make('Qwertyuiop@1'),
                    'gender'            => $user['gender'],
                    'profile_picture'   => 'default-avatar.png',
                    'date_of_birth'     => '1995-01-01',
                    'email_verified_at' => Carbon::now(),
                    'tel_verified_at'   => Carbon::now(),
                    'location'          => 'Lagos, Nigeria',
                    'address'           => 'No 1, Example Street, Lagos',
                    'role'              => $group['role'],
                    'inducted_by'       => null,
                ]);
            }
        }
    }
}

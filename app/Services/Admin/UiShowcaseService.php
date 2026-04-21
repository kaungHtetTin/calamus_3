<?php

namespace App\Services\Admin;

class UiShowcaseService
{
    public function getShowcaseData(): array
    {
        return [
            'metrics' => [
                [
                    'label' => 'Active Users',
                    'value' => '24.8K',
                    'change' => '+12.4%',
                    'tone' => 'success',
                ],
                [
                    'label' => 'API Requests',
                    'value' => '1.2M',
                    'change' => '+8.1%',
                    'tone' => 'info',
                ],
                [
                    'label' => 'Error Rate',
                    'value' => '0.32%',
                    'change' => '-0.08%',
                    'tone' => 'warning',
                ],
                [
                    'label' => 'Revenue',
                    'value' => '$18.4K',
                    'change' => '+5.6%',
                    'tone' => 'secondary',
                ],
            ],
            'environments' => [
                [
                    'name' => 'Production',
                    'region' => 'asia-southeast1',
                    'status' => 'Healthy',
                    'traffic' => '78%',
                ],
                [
                    'name' => 'Staging',
                    'region' => 'asia-east1',
                    'status' => 'Monitoring',
                    'traffic' => '17%',
                ],
                [
                    'name' => 'Development',
                    'region' => 'local',
                    'status' => 'Healthy',
                    'traffic' => '5%',
                ],
            ],
            'activity' => [
                [
                    'title' => 'New deployment completed',
                    'description' => 'Admin panel UI package deployed to Production.',
                    'time' => '4 minutes ago',
                ],
                [
                    'title' => 'Database backup',
                    'description' => 'Nightly backup finished successfully.',
                    'time' => '28 minutes ago',
                ],
                [
                    'title' => 'User role updated',
                    'description' => 'Moderator permissions synced with policy.',
                    'time' => '1 hour ago',
                ],
            ],
        ];
    }
}

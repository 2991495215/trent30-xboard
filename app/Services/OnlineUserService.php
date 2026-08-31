<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class OnlineUserService
{
    /**
     * Count active accounts across all nodes without double-counting users.
     */
    public function count(): int
    {
        $keys = array_merge(
            Redis::connection('cache')->keys('*USER_ONLINE_CONN_*'),
            Redis::keys('user_devices:*')
        );

        return count(self::extractUserIds($keys));
    }

    /**
     * @param iterable<string> $keys
     * @return array<int>
     */
    public static function extractUserIds(iterable $keys): array
    {
        $userIds = [];

        foreach ($keys as $key) {
            if (preg_match('/(?:USER_ONLINE_CONN_.+_|user_devices:)(\d+)$/', $key, $matches)) {
                $userIds[(int) $matches[1]] = true;
            }
        }

        $userIds = array_keys($userIds);
        sort($userIds, SORT_NUMERIC);

        return $userIds;
    }
}

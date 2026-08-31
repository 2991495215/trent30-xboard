<?php

namespace Tests\Unit\Services;

use App\Services\OnlineUserService;
use PHPUnit\Framework\TestCase;

class OnlineUserServiceTest extends TestCase
{
    public function testExtractUserIdsDeduplicatesLegacyAndDeviceKeys(): void
    {
        $keys = [
            'xboard_database_xboard_cacheUSER_ONLINE_CONN_hysteria_12_7',
            'xboard_database_user_devices:7',
            'xboard_database_user_devices:42',
            'xboard_database_xboard_cacheSERVER_HYSTERIA_ONLINE_USER_12',
        ];

        $this->assertSame([7, 42], OnlineUserService::extractUserIds($keys));
    }
}

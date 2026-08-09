<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\NodeResource;
use App\Models\Server;
use App\Models\ServerMachine;
use App\Models\ServerMachineLoadHistory;
use App\Models\User;
use App\Services\OnlineUserService;
use App\Services\ServerService;
use App\Services\UserService;
use Illuminate\Http\Request;

class ServerController extends Controller
{
    public function fetch(Request $request)
    {
        $user = User::find($request->user()->id);
        $servers = [];
        $userService = new UserService();
        if ($userService->isAvailable($user)) {
            $servers = ServerService::getAvailableServers($user);
        }
        $onlineTotal = (new OnlineUserService())->count();
        $eTag = sha1(json_encode([
            'servers' => array_column($servers, 'cache_key'),
            'online_total' => $onlineTotal,
        ]));
        if (strpos($request->header('If-None-Match', ''), $eTag) !== false ) {
            return response(null,304);
        }
        $data = NodeResource::collection($servers);
        return response([
            'online_total' => $onlineTotal,
            'data' => $data
        ])->header('ETag', "\"{$eTag}\"");
    }

    public function machine(Request $request)
    {
        $params = $request->validate([
            'node_id' => 'required|integer',
            'history_limit' => 'nullable|integer|min:1|max:1440',
            'range_hours' => 'nullable|integer|min:1|max:24',
        ]);

        $server = Server::find($params['node_id']);
        if (!$server || empty($server->machine_id)) {
            return response(['data' => null]);
        }

        $userGroup = (string) $request->user()->group_id;
        if (!in_array($userGroup, array_map('strval', $server->group_ids ?? []), true)) {
            return response(['data' => null]);
        }

        $machine = ServerMachine::find($server->machine_id);
        $query = ServerMachineLoadHistory::query()
            ->where('machine_id', $server->machine_id);

        if (!empty($params['range_hours'])) {
            $query->where('recorded_at', '>=', now()->subHours((int) $params['range_hours'])->timestamp);
        }

        $history = $query
            ->orderByDesc('recorded_at')
            ->limit((int) ($params['history_limit'] ?? 60))
            ->get([
                'cpu',
                'mem_total',
                'mem_used',
                'disk_total',
                'disk_used',
                'net_in_speed',
                'net_out_speed',
                'recorded_at',
            ])
            ->reverse()
            ->values();

        return response([
            'data' => [
                'machine' => $machine ? [
                    'id' => $machine->id,
                    'name' => $machine->name,
                    'is_active' => (bool) $machine->is_active,
                    'last_seen_at' => $machine->last_seen_at,
                    'load_status' => $machine->load_status,
                ] : null,
                'history' => $history,
            ],
        ]);
    }
}

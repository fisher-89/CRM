<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ClientRequest;
use App\Models\AuthorityGroups;
use App\Models\ClientLogs;
use App\Services\Admin\ClientLogsService;
use App\Services\Admin\ClientsService;
use Illuminate\Http\Request;

class ClientLogsController extends Controller
{
    protected $logs;

    public function __construct(ClientLogsService $clientLogsService)
    {
        $this->logs = $clientLogsService;
    }

    /**
     * 客户记录
     *
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $obj = $this->clientReadingAuth($request);
        return $this->logs->getClientLogsList($request, $obj);
    }

    /**
     * 客户信息还原
     *
     * @param Request $request
     * @return mixed
     */
    public function restore(Request $request)
    {
        $this->authority($request);
        $this->last($request->route('id'));
        return $this->logs->restoreClient($request);
    }

    /**
     * 权限控制
     *
     * @param $request
     */
    protected function authority($request)
    {
//        $staff = ClientLogs::where('id', $request->route('id'))->value('staff_sn');
        $OA = $request->user()->authorities['oa'];
        if (!in_array('186',$OA)) {
            abort(401, '你没有权限操作');
        }
    }

    /**
     * @param $request
     */
    protected function last($id)
    {
        $logs = ClientLogs::find($id);
        if (strstr($logs->type, '还原') || strstr($logs->type, '删除')) {
            abort(400, '错误操作:选择类型错误');
        }
        if ($logs->status != 1) {
            abort(400, '错误操作:无法还原该数据');
        }
        if ($logs->changes == []) {
            abort(400, '错误操作:未找到还原数据');
        }
    }

    protected function clientReadingAuth($request)
    {
        $staff = AuthorityGroups::whereHas('staffs', function ($query) use ($request) {
            $query->where('staff_sn', $request->user()->staff_sn);
        })->with('visibles')->get();
        if ((bool)$staff === false) {
            abort(401, '暂无权限');
        }
        return $staff;
    }
}
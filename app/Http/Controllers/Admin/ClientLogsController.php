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
        $obj=$this->clientReadingAuth($request);
        return $this->logs->getClientLogsList($request,$obj);
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
        $staff = ClientLogs::where('id', $request->route('id'))->value('staff_sn');
        if ($request->user()->staff_sn !== $staff) {
            abort(401, '你没有权限操作');
        }
    }

    /**
     * 倒数第二条  待确定要不要取倒数第二条，取第二条逻辑：先全部倒数拿出来，用循环i=2 就可以拿到id
     * 还原最后一条bug，当最后一条数据是删除数据，无法还原，也无法还原之前的数据
     * @param $request
     */
    protected function last($id)
    {
//        $clientId = ClientLogs::where('id', $id)->value('client_id');
//        $logs = ClientLogs::orderBy('id', 'desc')->where('client_id', $clientId)->first();
        $logs = ClientLogs::find($id);
        if (strstr($logs->type,'还原') || strstr($logs->type,'删除')) {
            abort(400,'错误操作');
        }
//        if ($logs->id != $id) {
//            abort(401, '无法操作之前数据');
//        }
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
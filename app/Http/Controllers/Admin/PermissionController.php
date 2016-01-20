<?php namespace App\Http\Controllers\Admin;

use Zizaco\Entrust\EntrustPermission;
use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Model\Role;
use App\Model\User;
use App\Model\Permission;
use App\Services\Helper;
use Session;
use Redirect;
class PermissionController extends Controller {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index(Request $request) {
		return view('admin.permission.index');
		// $owner = new Role();
		// $owner->app_id = 2;
		// $owner->name         = 'owner';
		// $owner->title = 'Project Owner'; // optional
		// $owner->description  = 'User is the owner of a given project'; // optional
		// $owner->save();
		// $role = Role::find(16);
		// $role->delete();
		// return view('admin.role.index');
		$admin = Role::find(20);
		// var_dump($admin);exit;
		$user = User::find(5);
		// $user->attachRole($admin);
		$user->detachRole($admin);
		$createPost = new Permission();
		$createPost->name         = 'create-post';
		$createPost->app_id         = 2;
		$createPost->title = 'Create Posts'; // optional
		$createPost->description  = 'create new blog posts'; // optional
		// $createPost->save();
		// $admin->attachPermission($createPost);
	}

	public function app(Request $request) {
		return view('admin.permission.app');
	}

	public function lists(Request $request) {
		$app_user_ids = '';
		// if(isset($_GET['type']) && $_GET['type'] == 'app') {
			// $current_app_id = Session::get('current_app_id');
			// $app_user_ids = Role::where('app_id', '=', $current_app_id)->lists('id');
		// }
		// DB::enableQueryLog();
		$columns = $_POST['columns'];
        $order_column = $_POST['order']['0']['column'];
        if($columns[$order_column]['data'] == 'group_name') {//那一列排序，从0开始
            $columns[$order_column]['data'] = 'group_id';
        }
		$order_dir = $_POST['order']['0']['dir'];//ase desc 升序或者降序
		$search = $_POST['search']['value'];//获取前台传过来的过滤条件
		$start = $_POST['start'];//从多少开始
		$length = $_POST['length'];//数据长度
		$fields = array('id', 'group_id', 'group_order_id', 'order_id', 'name', 'title', 'description', 'created_at', 'updated_at');
		if(isset($_GET['type']) && $_GET['type'] == 'app') {
			$recordsTotal = Permission::where('app_id', Session::get('current_app_id'))->where('group_id', '<>', 0)->count();
		} else {
			$recordsTotal = Permission::where('group_id', '<>', 0)->count();
		}
		if(strlen($search)) {
			$roles = Permission::where(function ($query) use ($search) {
				if(isset($_GET['type']) && $_GET['type'] == 'app') {
					$query->where('app_id', Session::get('current_app_id'));
				}})
                ->where("group_id", "<>", 0)
				->where("name" , 'LIKE',  '%' . $search . '%')
				->orWhere("title" , 'LIKE',  '%' . $search . '%')
				->orWhere("description" , 'LIKE',  '%' . $search . '%')
				->orderby($columns[$order_column]['data'], $order_dir)
				->skip($start)
				->take($length)
				->get($fields);
				// ->toArray();
			$recordsFiltered = count($roles);
		} else {
            $roles = Permission::where(function ($query) use ($search) {
				if(isset($_GET['type']) && $_GET['type'] == 'app') {
					$query->where('app_id', Session::get('current_app_id'));
				}})
                ->where("group_id", "<>", 0)
				->orderby($columns[$order_column]['data'], $order_dir)
				->skip($start)
				->take($length)
				->get($fields);
			$recordsFiltered = $recordsTotal;
		}
        foreach($roles as $v) {
            $v['group_name'] = $v->group->title;
            unset($v['group']);
        }
		$return_data = array(
							"draw" => intval($_POST['draw']),
							"recordsTotal" => intval($recordsTotal),
							"recordsFiltered" => intval($recordsFiltered),
							"data" => $roles
						);
		$jsonp = preg_match('/^[$A-Z_][0-9A-Z_$]*$/i', $_GET['callback']) ? $_GET['callback'] : false;
		if($jsonp) {
		    echo $jsonp . '(' . json_encode($return_data, JSON_UNESCAPED_UNICODE) . ');';
		} else {
		    echo json_encode($return_data, JSON_UNESCAPED_UNICODE);
        }

    }
	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create() {
        $groups = Permission::where('app_id', Session::get('current_app_id'))->where('group_id', 0)->get(array('id', 'title'));
		return view('admin.permission.create')->withGroups($groups);
    }

	public function createGroup() {
		return view('admin.permission.createGroup');
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store(Request $request) {
        $permission = Permission::create(array(
            'app_id' => Session::get('current_app_id'),
            'group_id' => is_null($request->group_id) ? 0 : $request->group_id,
            'name' => $request->name,
			'title' => $request->title,
			'description' => $request->description,
		));
		if ($permission) {
			session()->flash('success_message', '添加成功');
			return redirect('/admin/permission/app');
		} else {
			return redirect()->back()->withInput()->withErrors('保存失败！');
		}
		//
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		//
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		return view('admin.permission.edit')->withPermission(Permission::find($id));
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update(Request $request, $id)
	{
        $permission = Permission::where('id', $id)->update(array(
            'app_id' => Session::get('current_app_id'),
            'name' => $request->name,
			'title' => $request->title,
			'description' => $request->description,
		));
		if ($permission) {
			session()->flash('success_message', '权限修改成功');
			return Redirect::to('/admin/permission/app');
		} else {
			return Redirect::back()->withInput()->withErrors('保存失败！');
		}
		//
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		//
	}

	public function delete()
	{
		DB::beginTransaction();
		try {
			$ids = $_POST['ids'];
			// Auth::user()->can('delete-all-app');
			$result = Permission::whereIn('id', $ids)->delete();

            // DB::table('oauth_clients')->where('id', $id)->delete();
			DB::commit();
			Helper::jsonp_return(0, '删除成功', array('deleted_num' => $result));
		} catch (Exception $e) {
			DB::rollBack();
			throw $e;
			Helper::jsonp_return(1, '删除失败', array('deleted_num' => 0));
		}
	}
}

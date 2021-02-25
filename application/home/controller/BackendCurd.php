<?php

namespace application\home\controller;

use library\mysmarty\Model;

/**
 * 具有查询，添加，删除，编辑操作的权限控制类
 * @package application\home\controller
 */
class BackendCurd extends Backend
{
    // 主数据库名
    protected string $database = '';
    // 主表名
    protected string $table = '';
    // 查询的字段名
    protected string $field = '';
    // 主表主键名
    protected string $primaryKey = 'id';
    // 排序
    protected string $order = '';
    // 每页多少条数据
    protected int $size = 10;
    // 分页变量
    protected string $varPage = 'page';
    // 假的总统计数据，大于0的时候将不在统计真实的总数据，加快列表查询
    protected int $totalNum = 0;
    // 关联条件，多个关联数据用二维数组
    // ['admin','admin.id=login_log.admin_id']
    // ['admin','admin.id=login_log.admin_id','right join']
    // [['admin','admin.id=login_log.admin_id','right join'],['admin','admin.id=login_log.admin_id']]
    protected array $joinCondition = [];
    // 搜索条件，用 | 分隔非主表字段，用 / 分隔接收的数据类型。值代表使用何种搜索。
    // ['admin_id', 'id/i', 'admin|name' => 'like', 'admin|gender/i']
    protected array $searchCondition = [];
    // 获取数据的类型，1 获取当前表的所有数据。2 获取当前表与自己关联的数据。3 获取当前表与自己角色下的关联所有数据（包含自己的数据）
    protected int $dataType = 1;
    // 关联的字段ID。$dataType 不为1时，必须填写
    protected string $dataField = 'admin_id';
    // 表单验证文件
    // 比如：\application\home\validate\Admin::class;
    protected string $validate;
    // 是否允许执行列表方法
    protected bool $allowIndexMethod = true;
    // 是否允许执行添加方法
    protected bool $allowAddMethod = false;
    // 是否允许执行编辑方法
    protected bool $allowEditMethod = false;
    // 是否允许执行删除方法
    protected bool $allowDeleteMethod = false;

    public function __construct()
    {
        parent::__construct();
        if (empty($this->database)) {
            // 默认数据库名
            $this->database = config('database.mysql.database');
        }
        if (empty($this->table)) {
            // 默认表名
            $class = substr(static::class, strrpos(static::class, '\\') + 1);
            $this->table = toDivideName($class);
        }
        if (empty($this->order) && !empty($this->primaryKey)) {
            // 默认排序
            $this->order = $this->table . '.' . $this->primaryKey . ' desc';
        }
        if (empty($this->field)) {
            // 默认查询字段
            $this->field = $this->table . '.*';
        }
    }

    /**
     * 列表
     */
    public function index()
    {
        if (!$this->allowIndexMethod) {
            $this->error('您无权访问此页面');
        }
        $model = Model::getInstance()->setDatabase($this->database)
            ->setTable($this->table)
            ->field($this->field)
            ->order($this->order);
        // 处理关联查询
        if (!empty($this->joinCondition)) {
            $joinConditions = [];
            if (count($this->joinCondition) === count($this->joinCondition, COUNT_RECURSIVE)) {
                // 一维数组join条件
                $joinConditions[] = $this->joinCondition;
            } else {
                // 二维数组join条件
                $joinConditions = $this->joinCondition;
            }
            foreach ($joinConditions as $joinCondition) {
                $joinConditionNum = count($joinCondition);
                switch ($joinConditionNum) {
                    case 2:
                        // 使用left join
                        $model->leftJoin($joinCondition[0], $joinCondition[1]);
                        break;
                    case 3:
                        // 使用join
                        $model->join($joinCondition[0], $joinCondition[1], $joinCondition[2]);
                        break;
                }
            }
        }
        // 处理搜索条件
        $searchParam = '';
        if (!empty($this->searchCondition)) {
            foreach ($this->searchCondition as $k => $v) {
                if (is_int($k)) {
                    // $v 为查询字段
                    $searchStr = $v;
                    $searchOp = '';
                } else {
                    // $k 为查询字段，$v为查询条件
                    $searchStr = $k;
                    $searchOp = $v;
                }
                $pos = strpos($searchStr, '/');
                $searchType = 's';
                if ($pos !== false) {
                    $searchType = substr($searchStr, $pos + 1);
                    $searchStr = substr($searchStr, 0, $pos);
                }
                // 传过来的搜索值
                if (!isset($_GET[$searchStr])) {
                    $this->assign($searchStr, '');
                    continue;
                }
                $searchVal = match ($searchType) {
                    's' => getString($searchStr),
                    'i' => getInt($searchStr)
                };
                $this->assign($searchStr, $searchVal);
                $searchParam .= '&' . $searchStr . '=' . $searchVal;
                // 修改查询字段
                if (!str_contains($searchStr, '|')) {
                    $searchStr = $this->table . '.' . $searchStr;
                } else {
                    $searchStr = str_ireplace('|', '.', $searchStr);
                }
                if (is_string($searchVal)) {
                    // 字符串
                    if (!empty($searchVal)) {
                        $searchOp = $searchOp ?: 'like';
                        if ('like' === strtolower($searchOp)) {
                            $searchVal = '%' . $searchVal . '%';
                        }
                        $model->where($searchStr, $searchVal, $searchOp);
                    }
                } else if (is_int($searchVal)) {
                    // 数字
                    $searchOp = $searchOp ?: '=';
                    $model->where($searchStr, $searchVal, $searchOp);
                }
            }
        }
        // 处理数据类型
        if (1 !== $this->dataType) {
            if (empty($this->dataField)) {
                $this->error('数据关联字段不能为空');
            }
            // 修改关联字段
            $this->dataField = $this->table . '.' . $this->dataField;
            switch ($this->dataType) {
                case 2:
                    // 仅查询自己的数据
                    $model->where($this->dataField, $this->smartyAdmin['id']);
                    break;
                case 3:
                    // 查询自己的数据和角色下用户的数据
                    if (!$this->isSuperAdmin) {
                        $adminIds = $this->getAdminIds();
                        $model->in($this->dataField, $adminIds);
                    }
                    break;
            }
        }
        if (empty($searchParam) && $this->totalNum > 0) {
            $list = $model->paginateByCount($this->totalNum, $this->size, false, 5, $this->varPage);
        } else {
            $list = $model->paginate($this->size, false, 5, $this->varPage);
        }
        $this->assign('searchParam', $searchParam);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 添加
     */
    public function add()
    {
        if (!$this->allowAddMethod) {
            $this->error('您无权访问此页面');
        }
        if (isPost()) {
            $data = $_POST;
            // 添加当前管理员id
            if (1 !== $this->dataType) {
                if (!isset($data[$this->dataField])) {
                    $data[$this->dataField] = $this->smartyAdmin['id'];
                }
            }
            if (!empty($this->validate)) {
                $validate = new $this->validate;
                if ($validate->scene('add')->check($data) === false) {
                    $this->error($validate->getError());
                }
            }
            $num = Model::getInstance()->setDatabase($this->database)
                ->setTable($this->table)
                ->allowField(true)
                ->add($data);
            if ($num > 0) {
                $this->success('添加成功', getAbsoluteUrl() . '/' . $this->currentMenu['url']);
            }
            $this->error('添加失败');
        }
        $this->display();
    }

    /**
     * 编辑
     */
    public function edit()
    {
        if (!$this->allowEditMethod) {
            $this->error('您无权访问此页面');
        }
        $id = getInt($this->primaryKey);
        if (empty($id)) {
            $this->error('参数错误');
        }
        $data = $this->getDataByPrimaryKey($id);
        if (isPost()) {
            $data = $_POST;
            // 添加当前管理员id
            if (1 !== $this->dataType) {
                if (!isset($data[$this->dataField])) {
                    $data[$this->dataField] = $this->smartyAdmin['id'];
                }
            }
            if (!empty($this->validate)) {
                $validate = new $this->validate;
                if ($validate->scene('edit')->check($data) === false) {
                    $this->error($validate->getError());
                }
            }
            $num = Model::getInstance()->setDatabase($this->database)
                ->setTable($this->table)
                ->allowField(true)
                ->eq($this->primaryKey, $id)
                ->update($data);
            if ($num > 0) {
                $this->success('编辑成功', getAbsoluteUrl() . '/' . $this->currentMenu['url']);
            }
            $this->error('编辑失败');
        }
        $this->assign('data', $data);
        $this->display();
    }

    /**
     * 根据主键查询数据
     * @param int $id 值
     * @return array
     */
    protected function getDataByPrimaryKey(int $id): array
    {
        $data = Model::getInstance()->setDatabase($this->database)
            ->setTable($this->table)
            ->eq($this->primaryKey, $id)
            ->find();
        if (empty($data)) {
            $this->error('数据不存在');
        }
        if (!$this->isSuperAdmin && 1 !== $this->dataType) {
            switch ($this->dataType) {
                case 2:
                    // 仅查询自己的数据
                    if ($this->smartyAdmin['id'] != $data[$this->dataField]) {
                        $this->error('您没有权限操作');
                    }
                    break;
                case 3:
                    // 查询自己的数据和角色下用户的数据
                    $adminIds = $this->getAdminIds();
                    if (!in_array($data[$this->dataField], $adminIds)) {
                        $this->error('您没有权限操作');
                    }
                    break;
            }
        }
        return $data;
    }

    /**
     * 删除
     */
    public function delete()
    {
        if (!$this->allowDeleteMethod) {
            $this->error('您无权访问此页面');
        }
        $id = getInt($this->primaryKey);
        if (empty($id)) {
            $this->error('参数错误');
        }
        $this->getDataByPrimaryKey($id);
        $num = Model::getInstance()->setDatabase($this->database)
            ->setTable($this->table)
            ->allowField(true)
            ->eq($this->primaryKey, $id)
            ->delete();
        if ($num > 0) {
            $this->success('删除成功', getAbsoluteUrl() . '/' . $this->currentMenu['url']);
        }
        $this->error('删除失败');
    }
}
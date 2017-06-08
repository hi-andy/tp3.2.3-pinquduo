<?php
/**
 * Created by PhpStorm.
 * User: Hua
 * Date: 2017/6/6
 * Time: 18:34
 */

namespace Admin\Controller;


use Think\Controller;
use Think\Page;

class SpecificationController extends Controller
{
    public function index()
    {
        $count = M('specification')->count();
        $page = new Page($count, 10);
        $show = bootstrap_page_style($page->show());
        $list = M('specification')->limit($page->firstRow, $page->listRows)->select();
        foreach ($list as &$value) {
            $value['is_show'] = $value['is_show'] ? '显示中' : '未显示';
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    // 显示添加界面
    public function add()
    {
        $this->display();
    }

    // 显示编辑界面
    public function edit($id)
    {
        $this->assign('specification', M('specification')->where('id='. $id)->find());
        $this->display();
    }

    // 保存添加
    public function save()
    {
        $data['name'] = I('name', '', 'trim');
        $data['create_time'] = date('Y-m-d H:i:s', time());
        if (!M('specification')->where('name=\''.$data['name'].'\'')->find()) {
            if (M('specification')->add($data)) {
                $this->success('规格添加成功！', U('Admin/Specification/index'));
            } else {
                $this->error('规格添加失败！', U('Admin/Specification/add'));
            }
        } else {
            $this->error('商品规格已存在！', U('Admin/Specification/add'));
        }
    }

    // 编辑更新
    public function update()
    {
        $id = I('id');
        $data['name'] = I('name');
        $data['update_time'] = date('Y-m-d H:i:s', time());
        if (M('specification')->where('id='.$id)->save($data)) {
            $this->success('规格修改成功！', U('Admin/Specification/index'));
        } else {
            $this->error('规格修改失败！', U('Admin/Specification/add'));
        }
    }

    // 删除
    public function delete($id)
    {
        $record = M('specification')->where('id='.$id)->find();
        if ($record) {
            if (M('specification')->where('id='.$id)->delete()) {
                $this->success('规格删除成功！', U('Admin/Specification/index'));
            } else {
                $this->error('规格删除失败！', U('Admin/Specification/index'));
            }
        } else {
            $this->error('规格不存在或已删除！', U('Admin/Specification/index'));
        }
    }
}
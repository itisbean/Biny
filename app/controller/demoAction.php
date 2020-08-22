<?php

namespace app\controller;

use App;
use app\dao\baseDAO;
use app\dao\userDAO;
use biny\lib\Event;
use biny\lib\Logger;
use biny\lib\Language;
use Constant;

/**
 * 演示Action
 * @property userDAO $userDAO
 * @property baseDAO $testDAO
 */
class demoAction extends baseAction
{
    //    // 权限配置
    //    protected function privilege()
    //    {
    //        return array(
    //            'login_required' => array(
    //                'actions' => '*', //绑定action
    //            ),
    //        );
    //    }

    public function action_index_bk()
    {
        $data = $this->buildingDAO->query();
        $view = $this->display('demo/list', [], [
            'data' => $data
        ]);
        $view->title = "List";
        return $view;
    }

    public function action_index()
    {
        $buid = $this->request->get('buid', 'b8f2f0dd-e4a4-11ea-a642-68cc6ec38f06');
        if ($buid) {
            $data = $this->buildingDAO->filter(['id' => $buid])->find();
        } else {
            $data = $this->buildingDAO->find();
        }
        $view = $this->display('demo/demo', [], [
            'buid' => $data['id'],
            'underFloors' => (int)$data['under_floors'],
            'groundFloors' => (int)$data['ground_floors']
        ]);
        $view->title = $data['name'];
        return $view;
    }

    /**
     * 保存楼层
     * @return string
     */
    public function action_saveFloor()
    {
        $buid = $this->request->post('buid');

        $id = $this->request->post('id', '');
        $data = $this->request->post('data', []);

        if (!$id) {
            $outline = $data['outline'];
            $latlngs = $data['latlngs'] ?: [];

            $id = $this->createUuid();
            $data = [
                'id' => $id,
                'building_id' => $buid,
                'outline' => implode(',', $outline),
                'temp_latlngs' => json_encode($latlngs),
                'high' => $data['floor'],
                'name' => ($data['floor'] > 0 ? 'L' : 'B') . abs($data['floor'])
            ];
            $this->floorDAO->add($data);
        } else {
        }

        $this->response->correct(['id' => $id]);
    }

    public function action_saveArea()
    {
        $fid = $this->request->post('fid');

        $data = $this->request->post('data', []);
        $id = $data['id'];

        $insert = ['id' => $id, 'floor_id' => $fid];
        if (!empty($data['outline'])) {
            $center = $this->getCenterPosition(array_chunk($data['outline'], 2));
            $insert['outline'] = implode(',', $data['outline']);
            $insert['center'] = implode(',', $center);
            
            $latlngs = $data['latlngs'] ?: [];
            $insert['temp_latlngs'] = json_encode($latlngs);
        }

        if (!empty($data['category'])) {
            $insert['category'] = $data['category'];
        }

        if (!empty($data['name'])) {
            $insert['name'] = $data['name'];
        }

        $this->areaDAO->createOrUpdate($insert);
        $this->response->correct(['id' => $id]);
    }

    public function action_savePoint()
    {
        $fid = $this->request->post('fid');

        $data = $this->request->post('data', []);
        $id = !empty($data['id']) ? $data['id'] : '';

        if (!$id) {
            $outline = $data['outline'];

            $id = $this->createUuid();
            $this->pointDAO->add([
                'id' => $id,
                'floor_id' => $fid,
                'type' => $data['category'],
                'outline' => implode(',', $outline),
                'name' => $data['name']
            ]);
        } else {
            $this->pointDAO->filter(['id' => $id])->update([
                'name' => $data['name']
            ]);
        }

        $this->response->correct(['id' => $id]);
    }

    public function action_deleteArea()
    {
        $id = $this->request->post('id');
        if (!$id) {
            $this->response->error();
        }
        $this->areaDAO->filter(['id' => $id])->delete();
        $this->response->correct();
    }

    public function action_deletePoint()
    {
        $id = $this->request->post('id');
        if (!$id) {
            $this->response->error();
        }
        $this->pointDAO->filter(['id' => $id])->delete();
        $this->response->correct();
    }

    public function action_getFloor()
    {
        $buid = $this->request->get('buid');
        $floor = $this->request->get('floor');
        $data = $this->floorDAO->filter(['building_id' => $buid, 'high' => $floor])->find();
        if ($data) {
            $data['temp_latlngs'] = json_decode($data['temp_latlngs'], true);

            $areas = $this->areaDAO->filter(['floor_id' => $data['id']])->query(['id', 'name', 'outline', 'temp_latlngs', 'category']);
            foreach ($areas as $k => $area) {
                $areas[$k]['temp_latlngs'] = json_decode($area['temp_latlngs'], true);
            }

            $points = $this->pointDAO->filter(['floor_id' => $data['id']])->query(['id', 'name', 'outline', 'type' => 'category']);
            foreach ($points as $k => $point) {
                $points[$k]['temp_latlngs'] = [explode(',', $point['outline'])];
            }

            $areas = array_merge($areas, $points);
            $data['areas'] = $areas;
        }

        $this->response->correct(['data' => $data]);
    }

    public function action_test()
    {
        $ret = [];
        $this->response->correct($ret['aaa'] === false);
    }

    private function createUuid()
    {
        $str = md5(uniqid(mt_rand(), true));
        $uuid  = substr($str, 0, 8) . '-';
        $uuid .= substr($str, 8, 4) . '-';
        $uuid .= substr($str, 12, 4) . '-';
        $uuid .= substr($str, 16, 4) . '-';
        $uuid .= substr($str, 20, 12);
        return $uuid;
    }

    private function getCenterFromDegrees($array)
    {
        if (!is_array($array)) return false;

        $num_coords = count($array);

        $X = 0.0;
        $Y = 0.0;
        $Z = 0.0;

        foreach ($array as $coord) {
            $lat = $coord[0] * pi() / 180;
            $lon = $coord[1] * pi() / 180;

            $a = cos($lat) * cos($lon);
            $b = cos($lat) * sin($lon);
            $c = sin($lat);

            $X += $a;
            $Y += $b;
            $Z += $c;
        }

        $X /= $num_coords;
        $Y /= $num_coords;
        $Z /= $num_coords;

        $lon = atan2($Y, $X);
        $hyp = sqrt($X * $X + $Y * $Y);
        $lat = atan2($Z, $hyp);

        return array($lat * 180 / pi(), $lon * 180 / pi());
    }

    private function getCenterPosition($array)
    {
        $xMin = 0;
        $xMax = 0;
        $yMin = 0;
        $yMax = 0;

        foreach ($array as $arr) {
            if ($arr[0] > $xMax) {
                $xMax = $arr[0];
            } elseif ($xMin == 0 || $arr[0] < $xMin) {
                $xMin = $arr[0];
            }
            if ($arr[1] > $yMax) {
                $yMax = $arr[1];
            } elseif ($yMin == 0 || $arr[1] < $yMin) {
                $yMin = $arr[1];
            }
        }

        $x = ($xMin + $xMax) / 2;
        $y = ($yMin + $yMax) / 2;

        return [$x, $y];
    }
}

<?php
namespace app\shell;
use biny\lib\Shell;
use biny\lib\Logger;
/**
 * Created by PhpStorm.
 * User: billge
 * Date: 16-10-1
 * Time: 上午11:00
 */
class testShell extends Shell
{
    public function init()
    {
        Logger::addLog('init');
        return 0;
    }

    public function action_index()
    {
        $this->response->correct('success');
    }

    public function action_center()
    {
        $data = $this->areaDAO->filter(['floor_id' => 'ccad030a-4fb8-b4a2-d970-2dbe068f94f9'])->query();
        foreach ($data as $val) {
            $outline = array_chunk(explode(',', $val['outline']), 2);
            $center = $this->getCenterPosition($outline);
            $this->areaDAO->filter(['id' => $val['id']])->update([
                'center' => implode(',', $center)
            ]);
        }
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
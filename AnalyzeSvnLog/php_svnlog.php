<?php
function format_version($output)
{
    $res = array();
    $res = explode('|', $output);
    $res['r'] = intval(str_replace('r', '', trim($res['0'])));
    unset($res[0]);
    $res['author'] = trim($res['1']);
    unset($res[1]);
    $res['date'] = substr(trim($res['2']), 0, 19);
    unset($res[2]);
    $res['time'] = strtotime($res['date']);
    return $res;
}

function draw_date_img($data, $name)
{
    $h = 720;
    $w = 1024;
    $img = imagecreate($w, $h);
    imagecolorallocate($img, 255, 255, 255);//设置底色
    $line_color = imagecolorallocate($img,  0, 0, 0);
    $textcolor = imagecolorallocate($img,  0, 255, 0);
    $side = 50;
    imageline($img, $side, $side, $side, $h - $side, $line_color);
    imageline($img, $side, $h - $side, $w - $side, $h - $side, $line_color);
    $h_step = floor(($h  - 2*$side) / max($data));
    for ($i = 1; $i <= max($data); $i++)
    {
        $tmp_h = $h - $side - $h_step * $i;
        imagestring($img, 5, $side, $tmp_h, $i, $textcolor);
    }
    $last_point = array($side, $h - $side);
    $index = 1;
    $w_step = floor(($w - $side * 2) / count($data));
    $line_color2 = imagecolorallocate($img,  255, 0, 0);
    foreach ($data as $key => $val)
    {
        $x = $side + $w_step * $index;
        $y = $h - $side - $h_step * $val;
        imageline($img, $last_point[0], $last_point[1], $x, $y, $line_color2);
        $last_point = array($x, $y);
        $index++;
        for($i = 0; $i < 10; $i++)
        {
            if($i  < 4) $x_offset = -10;
            else $x_offset = 0;
            imagestring($img, 5, $x + $x_offset, $h - $side - 10 + ($i % 5) * 10, $key[$i], $textcolor);
        }
       }
    imagepng($img, $name.'.png');
}

function print_data_to_csv($user, $data, $start_time, $end_time)
{
    $res_lines = array();
    $line1 = array();
    $line1[] = '分析用户';
    $line1[] = '分析开始日期';
    $line1[] = '分析结束 日期';
    $line1[] = '总计提交次数';
    $line1[] = '总计修改文件个数';
    $line1[] = '涉及redmine任务数';
    $line2 = array();
    $line2[] = $user;
    $line2[] = date('Y-m-d', $start_time);
    $line2[] = date('Y-m-d', $end_time);
    $line2[] = $data['svn_update_count'];
    $line2[] = $data['updatefiles']['count'];
    $line2[] = count($data['redmine']);
    $res_lines[] = $line1;
    $res_lines[] = $line2;
    
     $line3 = array();
     $res_lines[] = $line3;
     $line3[] = '每日提交次数(日期)';
     $line3[] = '次数';
     $res_lines[] = $line3;
     foreach ($data['dates'] as $k => $v)
     {
         $res_lines[] = array($k,$v);
     }
     
     $line4 = array();
     $res_lines[] = $line4;
     $line4[] = '被修改的文件';
     $line4[] = '修改次数';
     $res_lines[] = $line4;
     foreach ($data['updatefiles'] as $k => $v)
     {
         if($k != 'count')
         {
             $res_lines[] = array($k,$v);
         }
     }
     
    $line5 = array();
    $res_lines[] = $line5;
    $line5[] = 'redmine情况';
    $line5[] = '问题编号';
    $line5[] = '修改次数';
    $line5[] = '提交描述';
    $res_lines[] = $line5;
    foreach ($data['redmine'] as $key => $val)
    {
        foreach ($val['msg']  as $msg)
        {
            $res_lines[] = array('', $key,$val['count'], $msg);
        }
    }
    
    foreach ($res_lines as $val)
    {
        $str = implode(',',  $val);
        //$str = iconv('UTF-8', 'UCS-2', $str);
        file_put_contents($user.'_analyze.csv', $str.PHP_EOL, FILE_APPEND);
    }
}

// 0.set start time and endtime
$start_time = mktime(0, 0, 0, 8, 22, 2016);
$end_time = mktime(0, 0, 0, 8, 26, 2016);
if($start_time + 86400 > $end_time)
{
    exit("start time and end time must more than one day means 86400s");
}

// 1.update svn 
// exec('svn update  ~/svn_heguangyu', $output);

// 2.get start & end version info
$output = array();
exec('svn log -l 1 -q  ~/svn_heguangyu', $output);
$lastinfo = format_version($output[1]);

$startinfo = array();
$endinfo = array();
$go_flag = 0;
$last_r = $lastinfo['r'];
$step = 1000;
do
{
    $start_r = $last_r - $step;
    $cmd = "svn log -r $start_r:$last_r -q ~/svn_heguangyu";
    $last_r = $start_r;
    echo "exec : ".$cmd.PHP_EOL;
    $output = array();
    exec($cmd, $output);
    if($output)
    {
        for($i=0; $i<$step; $i++)
        {
            $fisrt = 2 * $i + 1;
            $second = 2 * ($i + 1) + 1;
            $fisrt_arr = format_version($output[$fisrt]);
            $second_arr = format_version($output[$second]);
            if($fisrt_arr['time'] <= $start_time && $start_time <= $second_arr['time'])
            {
                $startinfo = $fisrt_arr;
                $go_flag += 1;
            }
             if($fisrt_arr['time'] <= $end_time && $end_time <= $second_arr['time'])
            {
                $endinfo = $second_arr;
                $go_flag += 1;
            }
        }
    }
}while ($go_flag < 2 && $output);

if(!$startinfo && !$endinfo)
{
    exit("can not get start & end info");
}

// 3.分析日志
$cmd = "svn log -r ".$startinfo['r'].":".$endinfo['r']." -v --xml ~/svn_heguangyu";
echo "exec : ".$cmd.PHP_EOL;
$output = array();
exec($cmd, $output);
$xml = implode($output);
$xml_arr = simplexml_load_string($xml);
$res = array();
$log_users = array('heguangyu'=>1);
foreach ($xml_arr as $key => $val)
{
    $data = (array)$val;
    if(!isset($log_users[$data['author']]) || $log_users[$data['author']] != 1)
    {
        continue;
    }
    $res[$data['author']]['svn_update_count'] += 1; 
    $res[$data['author']]['dates'][substr($data['date'], 0, 10)] += 1; 
    $paths = (array)$data['paths'];
    foreach ($paths as $k=>$v)
    {
        if(!isset($res[$data['author']]['updatefiles'][$v]))
        {
            $res[$data['author']]['updatefiles']['count'] += 1;
            $res[$data['author']]['updatefiles'][$v] = 1;
        }
        else
        {
            $res[$data['author']]['updatefiles'][$v] += 1;
        }
    }
    $parten = '/\#\d+/i';
    preg_match_all($parten, $data['msg'], $match);
    foreach ($match[0] as $redmine)
    {
         $res[$data['author']]['redmine'][$redmine]['count'] += 1; 
         $res[$data['author']]['redmine'][$redmine]['msg'][] = $data['msg'];
    }
}
// draw_date_img($res['zhaocheng']['dates'], 'zhaocheng_dailyupdate');
print_r($res);
print_data_to_csv('heguangyu', $res['heguangyu'], $start_time, $end_time);




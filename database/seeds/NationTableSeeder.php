<?php

use Illuminate\Database\Seeder;

class NationTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            ["id"=> 1, "name"=> "汉族", "sort"=> 99],
            ["id"=> 2, "name"=> "蒙古族", "sort"=> 99],
            ["id"=> 3, "name"=> "回族", "sort"=> 99],
            ["id"=> 4, "name"=> "藏族", "sort"=> 99],
            ["id"=> 5, "name"=> "维吾尔族", "sort"=> 99],
            ["id"=> 6, "name"=> "苗族", "sort"=> 99],
            ["id"=> 7, "name"=> "彝族", "sort"=> 99],
            ["id"=> 8, "name"=> "壮族", "sort"=> 99],
            ["id"=> 9, "name"=> "布依族", "sort"=> 99],
            ["id"=> 10, "name"=> "朝鲜族", "sort"=> 99],
            ["id"=> 11, "name"=> "满族", "sort"=> 99],
            ["id"=> 12, "name"=> "侗族", "sort"=> 99],
            ["id"=> 13, "name"=> "瑶族", "sort"=> 99],
            ["id"=> 14, "name"=> "白族", "sort"=> 99],
            ["id"=> 15, "name"=> "土家族", "sort"=> 99],
            ["id"=> 16, "name"=> "哈尼族", "sort"=> 99],
            ["id"=> 17, "name"=> "哈萨克族", "sort"=> 99],
            ["id"=> 18, "name"=> "傣族", "sort"=> 99],
            ["id"=> 19, "name"=> "黎族", "sort"=> 99],
            ["id"=> 20, "name"=> "傈僳族", "sort"=> 99],
            ["id"=> 21, "name"=> "佤族", "sort"=> 99],
            ["id"=> 22, "name"=> "畲族", "sort"=> 99],
            ["id"=> 23, "name"=> "高山族", "sort"=> 99],
            ["id"=> 24, "name"=> "拉祜族", "sort"=> 99],
            ["id"=> 25, "name"=> "水族", "sort"=> 99],
            ["id"=> 26, "name"=> "东乡族", "sort"=> 99],
            ["id"=> 27, "name"=> "纳西族", "sort"=> 99],
            ["id"=> 28, "name"=> "景颇族", "sort"=> 99],
            ["id"=> 29, "name"=> "柯尔克孜族", "sort"=> 99],
            ["id"=> 30, "name"=> "土族", "sort"=> 99],
            ["id"=> 31, "name"=> "达斡尔族", "sort"=> 99],
            ["id"=> 32, "name"=> "仫佬族", "sort"=> 99],
            ["id"=> 33, "name"=> "羌族", "sort"=> 99],
            ["id"=> 34, "name"=> "布朗族", "sort"=> 99],
            ["id"=> 35, "name"=> "撒拉族", "sort"=> 99],
            ["id"=> 36, "name"=> "毛难族", "sort"=> 99],
            ["id"=> 37, "name"=> "仡佬族", "sort"=> 99],
            ["id"=> 38, "name"=> "锡伯族", "sort"=> 99],
            ["id"=> 39, "name"=> "阿昌族", "sort"=> 99],
            ["id"=> 40, "name"=> "普米族", "sort"=> 99],
            ["id"=> 41, "name"=> "塔吉克族", "sort"=> 99],
            ["id"=> 42, "name"=> "怒族", "sort"=> 99],
            ["id"=> 43, "name"=> "乌孜别克族", "sort"=> 99],
            ["id"=> 44, "name"=> "俄罗斯族", "sort"=> 99],
            ["id"=> 45, "name"=> "鄂温克族", "sort"=> 99],
            ["id"=> 46, "name"=> "崩龙族", "sort"=> 99],
            ["id"=> 47, "name"=> "保安族", "sort"=> 99],
            ["id"=> 48, "name"=> "裕固族", "sort"=> 99],
            ["id"=> 49, "name"=> "京族", "sort"=> 99],
            ["id"=> 50, "name"=> "塔塔尔族", "sort"=> 99],
            ["id"=> 51, "name"=> "独龙族", "sort"=> 99],
            ["id"=> 52, "name"=> "鄂伦春族", "sort"=> 99],
            ["id"=> 53, "name"=> "赫哲族", "sort"=> 99],
            ["id"=> 54, "name"=> "门巴族", "sort"=> 99],
            ["id"=> 55, "name"=> "珞巴族", "sort"=> 99],
            ["id"=> 56, "name"=> "基诺族", "sort"=> 99],
            ["id"=> 57, "name"=> "其他", "sort"=> 99]
        ];
        DB::table('nations')->truncate();
        DB::table('nations')->insert($data);
    }
}

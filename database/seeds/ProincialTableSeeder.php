<?php

use Illuminate\Database\Seeder;

class ProincialTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data=[
            ["id"=>1,"provincial"=>"北京市"],
            ["id"=>2,"provincial"=>"天津市"],
            ["id"=>3,"provincial"=>"上海市"],
            ["id"=>4,"provincial"=>"重庆市"],
            ["id"=>5,"provincial"=>"河北省"],
            ["id"=>6,"provincial"=>"山西省"],
            ["id"=>7,"provincial"=>"辽宁省"],
            ["id"=>8,"provincial"=>"吉林省"],
            ["id"=>9,"provincial"=>"黑龙江省"],
            ["id"=>10,"provincial"=>"江苏省"],
            ["id"=>11,"provincial"=>"浙江省"],
            ["id"=>12,"provincial"=>"安徽省"],
            ["id"=>13,"provincial"=>"福建省"],
            ["id"=>14,"provincial"=>"江西省"],
            ["id"=>15,"provincial"=>"山东省"],
            ["id"=>16,"provincial"=>"河南省"],
            ["id"=>17,"provincial"=>"湖北省"],
            ["id"=>18,"provincial"=>"湖南省"],
            ["id"=>19,"provincial"=>"广东省"],
            ["id"=>20,"provincial"=>"海南省"],
            ["id"=>21,"provincial"=>"四川省"],
            ["id"=>22,"provincial"=>"贵州省"],
            ["id"=>23,"provincial"=>"云南省"],
            ["id"=>24,"provincial"=>"陕西省"],
            ["id"=>25,"provincial"=>"甘肃省"],
            ["id"=>26,"provincial"=>"青海省"],
            ["id"=>27,"provincial"=>"台湾省"],
            ["id"=>28,"provincial"=>"内蒙古自治区"],
            ["id"=>29,"provincial"=>"广西壮族自治区"],
            ["id"=>30,"provincial"=>"西藏自治区"],
            ["id"=>31,"provincial"=>"宁夏回族自治区"],
            ["id"=>32,"provincial"=>"新疆维吾尔自治区"],
            ["id"=>33,"provincial"=>"香港特别行政区"],
            ["id"=>34,"provincial"=>"澳门特别行政区"],
        ];
        DB::table('provincial')->truncate();
        DB::table('provincial')->insert($data);
    }
}

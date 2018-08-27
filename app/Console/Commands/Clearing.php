<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Clearing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:temporaryFiles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $path=storage_path().'\app\public\temporary';
        $current_dir = opendir($path);
        while(($file = readdir($current_dir)) !== false) {
            $sub_dir = $path . DIRECTORY_SEPARATOR . $file;
            if($file == '.' || $file == '..') {
                continue;
            } else if(is_dir($sub_dir)) {
                del_file($sub_dir);
            } else {    //如果是文件,判断是30分钟以前的文件进行删除
                $files = fopen($path.'/'.$file,"r");
                $f =fstat($files);
                fclose($files);
                if($f['mtime']<(time()-30*60)){
                    @unlink($path.'/'.$file);
                }
            }
        }
    }
}

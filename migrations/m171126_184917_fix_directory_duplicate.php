<?php

use humhub\components\Migration;

class m171126_184917_fix_directory_duplicate extends Migration
{
    public $redundanteDir = '../external-calendar';

    public function up()
    {
        $path = dirname(__DIR__).'/'.$this->redundanteDir;

        if(is_dir($path)) {
            $this->rrmdir($path);
        }
    }

    public function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object)) {
                        $this->rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                    } else {
                        unlink($dir . DIRECTORY_SEPARATOR . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }

    public function down()
    {
        echo "m171126_184915_exdate does not support migration down.\n";
        return false;
    }

    /*
      // Use safeUp/safeDown to do migration with transaction
      public function safeUp()
      {
      }

      public function safeDown()
      {
      }
     */
}

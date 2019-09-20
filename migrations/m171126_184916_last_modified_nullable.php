<?php

use humhub\components\Migration;

class m171126_184916_last_modified_nullable extends Migration
{
    public function up()
    {
        $this->alterColumn('external_calendar_entry', 'last_modified', 'datetime DEFAULT NULL');
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

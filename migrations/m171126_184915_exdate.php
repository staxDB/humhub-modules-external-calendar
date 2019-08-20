<?php

use humhub\components\Migration;

class m171126_184915_exdate extends Migration
{
    public function up()
    {
        $this->addColumn('external_calendar_entry', 'exdate', $this->string()->null());
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

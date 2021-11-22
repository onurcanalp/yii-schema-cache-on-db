# yii-schema-cache-on-db
Yii schema cache is too slow on oracle, fix it 

Add MyOciSchema in to your components and define it in your config file's db->driverMap section like 'oci' => 'application.components.MyOciSchema'

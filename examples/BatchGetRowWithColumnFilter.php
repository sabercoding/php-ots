<?php
require (__DIR__ . "/../vendor/autoload.php");
require (__DIR__ . "/ExampleConfig.php");

use Aliyun\OTS\Consts\ComparatorTypeConst;
use Aliyun\OTS\Consts\PrimaryKeyTypeConst;
use Aliyun\OTS\Consts\RowExistenceExpectationConst;
use Aliyun\OTS\OTSClient as OTSClient;

$otsClient = new OTSClient (array (
    'EndPoint' => EXAMPLE_END_POINT,
    'AccessKeyID' => EXAMPLE_ACCESS_KEY_ID,
    'AccessKeySecret' => EXAMPLE_ACCESS_KEY_SECRET,
    'InstanceName' => EXAMPLE_INSTANCE_NAME
));

$request = array (
    'table_meta' => array (
        'table_name' => 'MyTable', // 表名为 MyTable
        'primary_key_schema' => array (
            array('PK0', PrimaryKeyTypeConst::CONST_INTEGER), // 第一个主键列（又叫分片键）名称为PK0, 类型为 INTEGER
            array('PK1', PrimaryKeyTypeConst::CONST_STRING)
        )
    ), // 第二个主键列名称为PK1, 类型为STRING
    
    'reserved_throughput' => array (
        'capacity_unit' => array (
            'read' => 0, // 预留读写吞吐量设置为：0个读CU，和0个写CU
            'write' => 0
        )
    ),
    "table_options" => array(
        "time_to_live" => -1,   // 数据生命周期, -1表示永久，单位秒
        "max_versions" => 2,    // 最大数据版本
        "deviation_cell_version_in_sec" => 86400  // 数据有效版本偏差，单位秒
    )
);
$otsClient->createTable ($request);
sleep (10);

$request = array (
    'table_name' => 'MyTable',
    'condition' => RowExistenceExpectationConst::CONST_IGNORE,
    'primary_key' => array (
        array('PK0', 1),
        array('PK1', 'Zhejiang')
    ),
    'attribute_columns' => array (
        array('attr1', 'Hangzhou')
    )
);
$response = $otsClient->putRow ($request);

$request = array (
    'table_name' => 'MyTable',
    'condition' => RowExistenceExpectationConst::CONST_IGNORE,
    'primary_key' => array (
        array('PK0', 2),
        array('PK1', 'Jiangsu')
    ),
    'attribute_columns' => array (
        array('attr1', 'Nanjing')
    )
);
$response = $otsClient->putRow ($request);

$request = array (
    'table_name' => 'MyTable',
    'condition' => RowExistenceExpectationConst::CONST_IGNORE,
    'primary_key' => array (
        array('PK0', 3),
        array('PK1', 'Guangdong')
    ),
    'attribute_columns' => array (
        array('attr1', 'Shenzhen')
    )
);
$response = $otsClient->putRow ($request);

$request = array (
    'tables' => array (
        array (
            'table_name' => 'MyTable',
            "max_versions" => 1,
            'rows' => array (
                array (
                    'primary_key' => array (
                        array('PK0', 1),
                        array('PK1', 'Zhejiang')
                    )
                ), // 第一行
                array (
                    'primary_key' => array (
                        array('PK0', 2),
                        array('PK1', 'Jiangsu')
                    )
                ), // 第二行
                array (
                    'primary_key' => array (
                        array('PK0', 3),
                        array('PK1', 'Guangdong')
                    )
                )
            ), // 第三行
            'column_filter' => array (
                'column_name' => 'attr1',
                'value' => 'Shenzhen',
                'comparator' => ComparatorTypeConst::CONST_NOT_EQUAL      // NOTE: 注意过滤掉也会返回一行读取成功的信息！
            )
        )
    )
);

$response = $otsClient->batchGetRow ($request);

// 处理返回的每个表
foreach ($response['tables'] as $tableData) {
  print "Handling table {$tableData['table_name']} ...\n";
  
  // 处理这个表下的每行数据
  foreach ($tableData['rows'] as $rowData) {
    
    if ($rowData['is_ok']) {
      // 处理读取到的数据
        $row = json_encode($rowData['primary_key']);
        print "Handling row: {$row}\n";

    } else {
            
            // 处理出错
            print "Error: {$rowData['error']['code']} {$rowData['error']['message']}\n";
        }
    }
}


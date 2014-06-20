phpsolrclient
=============
简单的solrclient,避免在简单需求下引入庞大的solr类库

使用说明
=============
```php
<?php
	$solrConfig = array(
        'host' => "localhost" ,      // solr服务器地址
        'port' => "8983" ,           // 端口
        'path' => "/solr",           // 路径 如 http://localhost:8983/solr/
        'timeout' => 10 			 // 查询超时时间
    );
	include(SolrClient.php);
	$solrClient=new SolrClient($solrConfig);

	//设置core
	$solrClient->setCore('test');

	//设置过滤
	$solrClient->setFilter("classid",1);
	//classid in (1,2,3)
	$solrClient->setFilter("classid",array(1,2,3));

	//设置排序
	$solrClient->setSort('id'); //id倒序
	$solrClient->setSort('id',true); //id正序
	//或者
	$solrClient->setSort(array('id','score')); //id倒序,score倒序
	//或者
	$solrClient->setSort(array('id'=>true,'score')); //id正序,score倒序
	//或者
	$solrClient->setSort();//默认为score倒序

	//设置limit
	$solrClient->setLimit(10);//取10条记录
	$solrClient->setLimit(10,5);//从5开始,取10条记录

	//设置过滤范围
	$solrClient->setRange('id',5,10);//过滤id从5到10

	//设置返回字段
	$solrClient->setField('id,classid');//返回id,classid

	//设置分组
	$solrClient->setGroupBy('classid',$limit,$sort); //按字段分组 $limit参数为每组返回条数 $sort为组内排序

	//字段加权
	$solrClient->addWeight('classid',100);

	//设置默认搜索字段
	$solrClient->setDefaultQueryField('title');

	//清除所有过滤条件
	$solrClient->resetFilters();

	//清楚所有分组条件
	$solrClient->resetGroupBy();

	//查询
	$data=$solrClient->query();//参数为solr的查询语法 如*:*

	//获取最后一次查询的记录总数
	$solrClient->getLastCount();
	$solrClient->count(); //getLastCount()的别名

	//获取服务器返回的原始数据
	$solrClient->getOriginalData();
	$solrClient->getData(); //getOriginalData()的别名

	//获取请求的header
	$solrClient->getResponseHeader();
	
	//获取查询的完整url地址 主要用于调试
	$solrClient->getQueryUrl(); // http://localhost:8983/test/select?q=*%3A*&wt=json&indent=true
	$solrClient->getUrl();//getQueryUrl()的别名

	//获取分词
	$solrClient->getWord('这个主要是获取分词,需要在设置core之后调用');

	//更新索引
	$solrClient->updateDoc($arr);//具体参数可参考solr文档或直接阅读本类的源码	

	query()以上的方法均支持链式调用 如:
	$solrClient->setCore('test')->setFilter('classid',1)->setSort('id')->setLimit(10,10)->query('test');

	除了query必须在最后调用,以外的方法没有循序要求
	更多详情可直接阅读源码,源码中有更详细的注释
	此类只是作为一个简单的solr操作类,如有更复杂的需求推荐使用solr提供的php类库

```
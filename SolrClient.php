<?php
/**
 * SolrClient
 */
class SolrClient{
	/**
	 * 服务器地址
	 * @var string
	 */
	private $_serverUrl;

	/**
	 * 查询URL
	 * @var string
	 */
	private $_solrUrl;

	/**
	 * 核心名称
	 * @var string
	 */
	private $_coreName;

	/**
	 * 偏移量
	 * @var int
	 */
	private $_start;

	/**
	 * 返回记录条数
	 * @var int
	 */
	private $_rows;

	/**
	 * 排序
	 * @var string
	 */
	private $_sort;

	/**
	 * 返回数据格式
	 * @var string
	 */
	private $_wt;

	/**
	 * 字段过滤
	 * @var array;
	 */
	private $_fq=array();

	/**
	 * 返回字段
	 * @var string
	 */
	private $_fl;

	/**
	 * 默认查询字段
	 * @var string
	 */
	private $_df;

	/**
	 * 全词字段加权
	 * @var string
	 */
	private $_pf=array();

	/**
	 * 分词字段加权
	 * @var string
	 */
	private $_qf=array();

	/**
	 * 字段加权
	 * @var string
	 */
	private $_defType='defType=edismax';

	/**
	 * 返回数据是否缩进
	 * @var boolean
	 */
	private $_indent=true;

	/**
	 * 是否开启debug
	 * @var boolean
	 */
	private $_debug=false;

	/**
	 * 查询语句
	 * @var string
	 */
	private $_query;

	/**
	 * groupby
	 * @var array
	 */
	private $_group=array();

	/**
	 * facet
	 * @var array
	 */
	private $_facet=array();

	/**
	 * 数组组合
	 * @var array
	 */
	private $_combinationData=array();

	/**
	 * 最后一次查询的统计数量
	 * @var int
	 */
	private $_lastCount;

	/**
	 * 请求数据
	 * @var array
	 */
	private $_responseHeader;

	/**
	 * 服务器返回的原始数据
	 * @var array
	 */
	private $_originalData;

	/**
	 * 查询超时时间
	 * @var int
	 */
	private $_timeout;

	/**
	 * 初始化solr服务器连接
	 * @param array $arrConfig [
	 *                         	'host'=>服务器地址
	 * 	                        'port'=>端口
	 * 	                        'path'=>服务器路径
	 * 	                        'timeout'=>查询超时时间
	 * 							]
	 */
	public function __construct (array $arrConfig = array()) {
		// default config infomation
		$arrDefaultConfig = array(
		                          'host' => "localhost" ,      // solr服务器地址
                            	  'port' => "8983" ,           // 端口
                            	  'path' => "/solr",
                            	  'timeout' => 10
                            	  );
		
		$arr = array_merge($arrDefaultConfig, $arrConfig);

		$this->_timeout=$arr['timeout'];
		$this->_serverUrl='http://'.$arr['host'];
		if($arr['port']!=''){
			$this->_serverUrl.=':'.$arr['port'];
		}
		if($arr['path']!=''){
			$this->_serverUrl.='/'.ltrim($arr['path'],'/');
		}
		$this->setRetrunMode();
	}

	/**
	 * 设置核心
	 * @param string $core 核心名
	 */
	public function setCore($core){
		$this->_coreName=$core;
		return $this;
	}

	/**
	 * 设置limit
	 * @param int $start 记录偏移条数
	 * @param int $limit 返回记录条数
	 */
	public function setLimit($limit,$start=0){
		if($start > 0){
			$this->_start=$start;
		}
		$this->_rows=$limit;
		return $this;
	}

	/**
	 * 设置排序
	 * @param array/string  $sort 排序字段
	 * @param boolean $asc  是否为升序
	 */
	public function setSort($sort='score',$asc=false){
		$this->_sort='';
		if(is_array($sort)){
			$tmpSort=array();
			foreach($sort as $k=>$v){
				if(is_numeric($k)){
					$tmpSort[]=$v.' desc';
				}else{
					if($v){
						$tmpSort[]=$k.' asc';
					}else{
						$tmpSort[]=$k.' desc';
					}
				}
			}
			$this->_sort=implode(',', $tmpSort);
		}else{
			$this->_sort=$sort;
			if($asc){
				$this->_sort.=' asc';
			}else{
				$this->_sort.=' desc';
			}
		}
		return $this;
	}

	/**
	 * 设置返回格式
	 * @param string $mode 返回格式[xml,json,php,phps]
	 */
	public function setRetrunMode($mode='php'){
		if(in_array($mode,array('xml','json','php','phps'))){
			$this->_wt=$mode;
		}
		return $this;
	}

	/**
	 * 过滤字段
	 * @param string $field  字段
	 * @param array  $filter 过滤值
	 */
	public function setFilter($field,$filter,$not=false){
		if($not){
			$field='!'.$field;
		}
		if(is_array($filter)){
			if(count($filter)>1){
				$this->_fq[]=urlencode($field.':('.implode(' OR ', $filter).')');
			}else{
				$this->_fq[]=urlencode($field.':'.implode(' OR ', $filter));
			}
		}else{
			$this->_fq[]=urlencode($field.':'.$filter);
		}
		return $this;
	}

	/**
	 * 过滤字段范围
	 * @param string $field 字段
	 * @param string $start 范围开始
	 * @param string $to    范围结束
	 */
	public function setRange($field,$start,$to){
		$this->_fq[]=urlencode($field.':'.'['.$start.' TO '.$to.']');
		return $this;
	}

	/**
	 * 清除字段过滤
	 */
	public function resetFilters(){
		$this->_fq=array();
		return $this;
	}

	/**
	 * 设置返回字段
	 * @param string $field 字段
	 */
	public function setField($field='*,score'){
		if(is_array($field)){
			$this->_fl=implode(',', $field);
		}else{
			$this->_fl=$field;
		}
		return $this;
	}

	/**
	 * 设置分组
	 * @param string  $field 字段
	 * @param integer $limit limit
	 * @param string  $sort  排序
	 */
	public function setGroupBy($field,$limit=1,$sort=''){
		if(!empty($this->_group)){
			$this->_group[]='group.field='.urlencode($field);
			return $this;
		}
		$this->_group[]='group=true';
		$this->_group[]='group.ngroups=true';
		$this->_group[]='group.field='.urlencode($field);
		$this->_group[]='group.limit='.$limit;
		if($sort!=''){
			$this->_group[]='group.sort='.urlencode($sort);
		}
		return $this;		
	}


	/**
	 * 清除分组
	 */
	public function resetGroupBy(){
		$this->_group=array();
		return $this;
	}

	/**
	 * 设置分面
	 * @param string $field    字段
	 * @param string $prefix   字段前缀
	 * @param boolean $sort     排序(true为count排序,false为自然排序)
	 * @param int $mincount 过滤最小count
	 */
	public function setFacet($field,$prefix='',$sort=true,$mincount=0){
		if(!empty($this->_facet)){
			$this->_facet[]='facet.field='.urlencode($field);
			return $this;
		}
		$this->_facet[]='facet=true';
		$this->_facet[]='facet.field='.urlencode($field);
		if($prefix!=''){
			$this->_facet[]='facet.prefix='.urlencode($prefix);
		}
		if(!$sort){
			$this->_facet[]='facet.sort=false';
		}
		if($mincount > 0){
			$this->_facet[]='facet.mincount='.$mincount;
		}
		return $this;
	}

	/**
	 * 清除分面
	 */
	public function resetFacet(){
		$this->_facet=array();
		return $this;
	}



	/**
	 * 字段加权
	 * @param string  $field  字段
	 * @param init  $weight 权重
	 * @param boolean $ispf   是否为全词
	 */
	public function addWeight($field,$weight,$ispf=true){
		if($ispf){
			$this->_pf[]=$field.'^'.$weight;
		}else{
			$this->_qf[]=$field.'^'.$weight;
		}
		return $this;
	}

	/**
	 * 设置默认查询字段
	 * @param string $field 字段
	 */
	public function setDefaultQueryField($field){
		$this->_df=$field;
		return $this;
	}

	/**
	 * 开启Debug
	 */
	public function openDebug(){
		$this->_debug=true;
		return $this;
	}


	/**
	 * 查询
	 * @param  string $query 查询语句
	 * @return [type]        [description]
	 */
	public function query($query='*:*'){
		if($query==''){
			$query='*:*';
		}
		//语法糖
		//参数格式 "AA BB CC"/2
		if(preg_match_all('/"(.+?)"\/(\d+)/', $query,$match)){
			$oldstr=$match[0];
			foreach($match[1] as $k=>$v){
				$this->_combinationData=array();
				$this->_combination(explode(' ', $v),$match[2][$k]);
				$query=str_replace($oldstr[$k], '(('.implode(') OR (', $this->_combinationData).'))', $query);
			}
		}
		$this->_query=$query;
		$tmpwt=$this->_wt;
		$this->setRetrunMode('json');
		$this->_solrUrl=$this->_parseUrl();

		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->_solrUrl);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->_timeout);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 0);
		$data = curl_exec($ch);
		curl_close($ch);


		//异常处理
		if(!empty($data)){
			$arrdata=json_decode($data);
			$newdata=$this->_objectToArray($arrdata);
			$this->_originalData=$newdata;
			$this->_responseHeader=$newdata['responseHeader'];
			if($tmpwt=='php'){
				if(isset($newdata['grouped'])){
					$tmpgroupdata=array();
					
					foreach($newdata['grouped'] as $k=>$v){
						$i=0;
						$this->_lastCount=$v['ngroups'];
						foreach($v['groups'] as $d){
							foreach($d['doclist']['docs'] as $key=>$val){
								$tmpgroupdata[$k][$i]=$val;
								$tmpgroupdata[$k][$i]['@groupValue']=$d['groupValue'];
								$tmpgroupdata[$k][$i]['@count']=$d['doclist']['numFound'];
								$i++;
							}
						}
					}

					$data=$tmpgroupdata;
					unset($tmpgroupdata,$i);
				}
				if(isset($newdata['facet_counts'])){
					$tmpfacetdata=array();
					$i=0;
					foreach($newdata['facet_counts']['facet_fields'] as $k=>$v){
						$this->_lastCount=count($v);
						while(list($key,$val)= each($v)) {
							$tmpfacetdata[$k][$i][$k]=$val;
							$tmpfacetdata[$k][$i]['@count']=current($v);
							next($v);
							$i++;
						}
					}
					$data=$tmpfacetdata;
					unset($tmpgroupdata,$i);
				}
				if(isset($newdata['response']) && !isset($newdata['facet_counts'])){
					$this->_lastCount=$newdata['response']['numFound'];
					$data=$newdata['response']['docs'];
				}
			}
		}else{
			return array();
		}

		return $data;
	}

	/**
	 * 获取最后一次查询的统计
	 * @return init 统计数量
	 */
	public function getLastCount(){
		return $this->_lastCount;
	}

	/**
	 * getLastCount的别名函数
	 * @return init 统计数量
	 */
	public function count(){
		return $this->getLastCount();
	}

	/**
	 * 返回服务器原始数据
	 * @return array 原始数据
	 */
	public function getOriginalData(){
		return $this->_originalData;
	}

	/**
	 * getOriginalData别名函数
	 * @return array 原始数据
	 */
	public function getData(){
		return $this->getOriginalData();
	}

	/**
	 * 获取请求数据
	 * @return array 数据
	 */
	public function getResponseHeader(){
		return $this->_responseHeader;
	}

	/**
	 * 获取查询URL
	 * @return string 查询URL
	 */
	public function getQueryUrl(){
		return urldecode($this->_solrUrl);
	}

	/**
	 * getQueryUrl别名函数
	 * @return string 查询URL
	 */
	public function getUrl(){
		return $this->getQueryUrl();
	}

	/**
	 * 获取分词
	 * @param  string $keyword 关键词
	 * @return array          分词结果
	 */
	public function getWord($keyword){
		$url='';
		$url.=$this->_serverUrl.'/'.$this->_coreName.'/analysis/field?analysis.fieldvalue='.urlencode($keyword);
		$tmpwt=$this->_wt;
		$this->setRetrunMode('json');
		if(isset($this->_wt)){
			$url.='&wt='.urlencode($this->_wt);
		}
		$this->setRetrunMode($tmpwt);
		if($tmpwt=='php'){
			$data=array();
		}else{
			$data='';
		}
		$this->_solrUrl=$url;

		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->_solrUrl);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->_timeout);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 0);
		$data=curl_exec($ch);
		curl_close($ch);

		if(!empty($data)){
			if($tmpwt=='php'){
				$arrdata=json_decode($data);
				$newdata=$this->_objectToArray($arrdata);
				$this->_originalData=$newdata;
				$newdata=array_pop($newdata['analysis']['field_names']);
				$newdata=$newdata['index'][1];
				$data=array();
				foreach($newdata as $v){
					$data[]=$v['text'];
				}
				$data=array_unique($data);
				if(count($data) > 1){
					if(array_search($keyword, $data)!==false){
						$key=array_search($keyword, $data);
						unset($data[$key]);
					}
				}
			}	
		}
		return $data;
	}

	/**
	 * 更新索引
	 * @param  [array] $data 更新文档的内容
	 */
	public function updateDoc($data){
		$url=$this->_serverUrl.$this->_coreName.'/update?wt=json';
		$updateData=array();
		$updateData['add']=array();
		$updateData['add']['boost']=1;
		$updateData['add']['commitWithin']=1000;
		$updateData['add']['overwrite']=true;
		$updateData['add']['doc']=$data;
		$updateData=json_encode($updateData);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(  
			'Content-Type: application/json; charset=utf-8',  
			'Content-Length: ' . strlen($updateData))  
		);  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $updateData);
		curl_setopt($ch, CURLOPT_TIMEOUT, 1);
		$output = curl_exec($ch);
		curl_close($ch);
	}

	/**
	 * 组装URL
	 * @return string $url 
	 */
	private function _parseUrl(){
		$url='';
		$url.=$this->_serverUrl.'/'.$this->_coreName.'/select?q='.urlencode($this->_query);
		if(!empty($this->_fq)){
			$url.='&fq='.implode('&fq=', $this->_fq);
		}
		if(isset($this->_sort)){
			$url.='&sort='.urlencode($this->_sort);
		}
		if(isset($this->_start)){
			$url.='&start='.$this->_start;
		}
		if(isset($this->_rows)){
			$url.='&rows='.$this->_rows;
		}
		if(isset($this->_fl)){
			$url.='&fl='.urlencode($this->_fl);
		}
		if(isset($this->_df)){
			$url.='&df='.urlencode($this->_df);
		}
		if(!empty($this->_group)){
			$url.='&'.implode('&', $this->_group);
		}
		if(!empty($this->_facet)){
			if(isset($this->_rows)){
				$this->_facet[]='facet.limit='.$this->_rows;
			}
			if(isset($this->_start)){
				$this->_facet[]='facet.offset='.$this->_start;
			}
			$url.='&'.implode('&', $this->_facet);
		}
		if(!empty($this->_defType)){
			$url.='&'.$this->_defType;
			if(!empty($this->_pf)){
				$url.='&pf='.implode(' ', $this->_pf);
			}
			if(!empty($this->_qf)){
				$url.='&qf='.implode(' ', $this->_qf);
			}
		}
		if(isset($this->_wt)){
			$url.='&wt='.urlencode($this->_wt);
		}
		if(isset($this->_indent)){
			$url.='&indent='.$this->_indent;
		}
		//初始化参数
		$this->_initParameter();
		return $url;
	}

	/**
	 * 对象转数组
	 * @param  obj $e 对象
	 * @return array    数组
	 */
	private function _objectToArray($e){
		$e=(array)$e;
		foreach($e as $k=>$v){
			if( gettype($v)=='resource' ) return;
			if( gettype($v)=='object' || gettype($v)=='array' )
				$e[$k]=(array)self::_objectToArray($v);
		}
		return $e;
	}

	/**
	 * 初始化参数
	 * @return [type] [description]
	 */
	private function _initParameter(){
		$this->_qf=array();
		$this->_pf=array();
		$this->setRetrunMode();
	}

	/**
	 * 数组组合
	 * @param  array  $arr 数组
	 * @param  integer $len 所需词根数量
	 * @param  string  $str 
	 * @return array       返回数组
	 */
	private function _combination($arr, $len=0, $str='') {
		$arr_len = count($arr);
		if($len == 0){
			$this->_combinationData[] = $str;
		}else{
			for($i=0; $i<$arr_len-$len+1; $i++){
				$tmp = array_shift($arr);
				if($str==''){
					self::_combination($arr, $len-1, $str.$tmp);
				}else{
					self::_combination($arr, $len-1, $str." ".$tmp);
				}
			}
		}
	}
}

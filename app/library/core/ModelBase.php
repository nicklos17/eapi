<?php
namespace core;
use \core\driver\PdoMysql;

class ModelBase
{
	
	/**
	 * 释放所有
	 *
	 * @var string
	 */
	private $fetchAll = 'fetchAll';

	/**
	 * 释放一行
	 *
	 * @var string
	 */
	private $fetchRow = 'fetch';

	/**
	 * 释放一列
	 *
	 * @var string
	 */
	private $fetchOne = 'fetchColumn';

	/**
	 * 预处理对象
	 *
	 * @var object
	 */
	private $stmt;

	/**
	 * 表名
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * 数据库对象
	 *
	 * @var \PDO
	 */
	protected $db;

	/**
	 * 创建模型
	 */
	public function __construct()
	{
		$this->db = PdoMysql::getInstance();
	}

	/**
	 * 对象销毁
	 *
	 * @return void
	 */
	public function __destruct()
	{
		$this->db = null;
	}
	
	
		/**
	 * 执行sql查询
	 *
	 * @param string sql语句
	 * @param array 参数数组
	 * @param string 返回结果绑定到的对象
	 * @param boolean 是否输出调试语句
	 * @return void
	 */
	public function query($sql, $params = array(), $class = 'stdClass', $debug = FALSE)
	{
		// 预处理绑定语句
		try
		{
			$this->stmt = $this->db->prepare($sql);
			if(!$this->stmt)
			{
				\core\Handler::appException('pdo prepare error with:'.$sql);
				throw new \Exception("system error",'49903');
			}
			// 参数绑定
			! $params or $this->bindValue($params);
			// 输出调试
			! $debug or $this->debug($sql, $params);
		
			// 执行一条sql语句
			if($this->stmt->execute())
			{
				// 设置解析模式
				$this->stmt->setFetchMode(\PDO::FETCH_CLASS, $class);
			}
			else
			{
				throw new \Exception('system error', '49904');
				// 获取数据库错误信息
				\core\Handler::appException($this->stmt->errorInfo()[2]);
			}
		}
		catch(\Exception $e)
		{
			\core\Handler::appException($e->getMessage());
			throw new \Exception('system error', '49902');
		}
	}

	/**
	 * 参数与数据类型绑定
	 *
	 * @param array
	 */
	private function bindValue($params)
	{
		foreach($params as $key => $value)
		{
			// 数据类型选择
			switch(TRUE)
			{
				case is_int($value):
					$type = \PDO::PARAM_INT;
					break;
				case is_bool($value):
					$type = \PDO::PARAM_BOOL;
					break;
				case is_null($value):
					$type = \PDO::PARAM_NULL;
					break;
				default:
					$type = \PDO::PARAM_STR;
			}
			// 参数绑定
			$this->stmt->bindValue($key, $value, $type);
		}
	}

	/**
	 * 获取所有记录集合
	 *
	 * @return \driver\mixed
	 */
	public function getAll()
	{
		return $this->fetch();
	}

	/**
	 * 获取一行记录
	 *
	 * @return \driver\mixed
	 */
	public function getRow()
	{
		return $this->fetch($this->fetchRow);
	}

	/**
	 * 获取一个字段
	 *
	 * @return \driver\mixed
	 */
	public function getOne()
	{
		return $this->fetch($this->fetchOne);
	}

	/**
	 * 解析数据库查询资源
	 *
	 * @param string fetchAll | fetch | fetchColumn
	 * @return mixed 查询得到返回数组或字符串,否则返回false或空数组
	 */
	private function fetch($func = 'fetchAll')
	{
		// 执行释放
		$result = $this->stmt->$func();
		// 删除资源
		unset($this->stmt);
		// 返回结果
		return $result;
	}

	/**
	 * 获取上次插入的id
	 *
	 * @return int
	 */
	public function lastInsertId()
	{
		return $this->db->lastInsertId();
	}

	/**
	 * 返回插入|更新|删除影响的行数
	 *
	 * @return int
	 */
	public function affectRow()
	{
		return $this->stmt->rowCount();
	}

	/**
	 * 返回结果集中的行数
	 *
	 * @return int
	 */
	public function resourceCount()
	{
		return $this->stmt->columnCount();
	}

	/**
	 * 输出预绑定sql和参数列表
	 *
	 * @return void
	 */
	public function debug($sql, $data)
	{
		foreach($data as $key => $placeholder)
		{
			// 字符串加上引号
			! is_string($placeholder) or ($placeholder = "'{$placeholder}'");
			// 替换
			$start = strpos($sql, $key);
			$end = strlen($key);
			$sql = substr_replace($sql, $placeholder, $start, $end);
		}
		
		echo $sql;
	}
	

	/**
	 * 执行插入
	 *
	 * @param array 插入键值对数组
	 * @param boolean 是否输出调试语句
	 * @return int 上一次插入的id
	 */
	public final function insert(array $insert, $debug = FALSE)
	{
		// 所有key
		$keys = array_keys($insert);
		// 所有value
		$vals = array_values($insert);
		foreach($keys as $k => $v)
		{
			$values[":{$v}"] = $vals[$k];
		}
		
		$keys = implode(',', $keys);
		$placeholder = implode(',', array_keys($values));
		
		// sql语句
		$sql = "INSERT INTO {$this->table}({$keys}) VALUES ({$placeholder})";
		// 执行sql语句
		$this->query($sql, $values, 'stdClass', $debug);
		// 插入的id
		return $this->lastInsertId();
	}

	/**
	 * 执行更新
	 *
	 * @param array $update 键值对数组
	 * @param array | string $where where查询条件
	 * @param boolean $debug 是否输出调试语句
	 * @return int 影响行数
	 */
	public final function update(array $update, $where = array(), $debug = FALSE)
	{
		foreach($update as $key => $val)
		{
			$set[] = "{$key}=:{$key}";
			$values[":{$key}"] = $val;
		}
		// set语句
		$set = implode(',', $set);
		// 获取sql子语句
		list($where, $values) = $this->where($where, $values);
		
		// sql语句
		$sql = "UPDATE {$this->table} SET {$set} {$where}";
		// 执行更新
		$this->query($sql, $values, 'stdClass', $debug);
		// 返回影响行数
		return $this->affectRow();
	}

	/**
	 * 拼接where子句 只支持and拼接 其他的需要自己构造SQL语句
	 *
	 * @param array $condition 键值对数组
	 * @param array $values 需要合并的数组
	 * @return array
	 */
	public final function where($condition, $values = array())
	{
		$where = $data = array();
		foreach($condition as $key => $option)
		{
			// false null array() ""的时候全部过滤
			if(! $option && ! is_int($option))
			{
				continue;
			}
			if(strpos($option, "%") !== FALSE)
			{
				$where[] = "{$key} LIKE :{$key}";
				$data[":{$key}"] = $option;
			}
			else
			{
				$where[] = "{$key}=:{$key}";
				$data[":{$key}"] = $option;
			}
		}
		
		$where = $where? implode(' AND ', $where): "";
		$where = $where? " WHERE {$where} ": $where;
		
		$values = array_merge($values, $data);
		return array($where,$values);
	}

	/**
	 * 分页
	 *
	 * @param string 偏移量,数量，偏移量可以省略
	 * @return array limit语句 | limit参数
	 */
	public final function limit($limit, $values = array())
	{	
		is_array($limit) or ($limit = explode(',', $limit));
		
		$offset = "";
		if((count($limit) == 2))
		{
			$offset = ":offset,";
			$page = $limit[0] - 1;
			$values[':offset'] = ($page < 1? 0: $page) * $limit[1];
		}
		
		$number = ":number";
		$values[':number'] = (int)array_pop($limit);
		
		return array(" LIMIT {$offset}{$number}",$values);
	}

	/**
	 * 执行删除
	 *
	 * @param array $where
	 * @param string 默认只删除一条,设置为null表示删除所有匹配到的行
	 * @return int 影响行数
	 */
	public function delete($where, $limit = "LIMIT 1", $debug = FALSE)
	{
		list($where, $values) = $this->where($where);
		
		$sql = "DELETE FROM {$this->table} {$where} {$limit}";
		
		$this->query($sql, $values, $debug);
		
		return $this->affectRow();
	}

	/**
	 * 统计行数
	 *
	 * @param array where子句键值对数组
	 * @param string 要统计的字段
	 * @param boolean 是否输出调试语句
	 * @return int
	 */
	public final function count($where = array(), $id = "*",$class='stdClass', $debug = FALSE)
	{
		list($where, $values) = $this->where($where);
		
		$sql = "SELECT COUNT({$id}) FROM {$this->table} {$where}";

		$this->query($sql, $values,$class,$debug);
		
		return $this->getOne();
	}

	/**
	 * 数据查询
	 * @param string $fields
	 * @param array $condition
	 * @param array $value
	 * @param string $class
	 * @param string $orderBy
	 * @param string $limit
	 * @param boolean $lock  是否加锁
	 * @return \driver\mixed
	 */
	public function getData($fields = '*', array $condition, array $value, $class = 'stdClass', $orderBy = false, $limit = false, $lock = false)
	{
		$where = '';
		$values = array();
		list($where, $values) = $this->where($condition,array());
		if($orderBy)
		{
			$where .= ' ORDER BY ' . $orderBy;
		}
		
		if($limit)
		{
			list($limitSql,$values) = $this->limit($limit,$values);
			$where.=$limitSql;
		}
		
		$sql = 'SELECT ' . $fields . ' FROM ' . $this->table . $where;

		if($lock)
		{
			$sql.= ' FOR UPDATE';
		}

		$this->query($sql, $values,$class);
		
		return $this->getAll();
	}

	/**
	 * 启动事务
	 */
	public final function begin()
	{
		$this->db->beginTransaction();
	}

	/**
	 * 提交事务
	 */
	public final function commit()
	{
		$this->db->commit();
	}
	
	/**
	 * 回滚事务
	 */
	public final function rollback()
	{
		$this->db->rollBack();
	}
}

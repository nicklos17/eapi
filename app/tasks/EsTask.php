<?php
class EsTask
{
	public function deleteDataAction()
	{
		$esFile = ROOT_PATH.'./app/tasks/es.id';
		$startId = @file_get_contents($esFile);
		$limit = 200;
		$newTaoModel = new \NewTaoModel();
		$list = $newTaoModel->getListForDeleteEs($startId,$limit);
		$es = \core\Config::item('elasticSearch');
		$client = \Elasticsearch\ClientBuilder::create()->setHosts(array($es['server']))->build();
		echo 'start:'.time()."\n";
		foreach ($list as $v)
		{
			$params = [
					'index' => $es['index'],
					'type' => $es['type'],
					'id' => $v->t_id
			];
			$response = $client->delete($params);
			$startId = $v->t_last_time;
		}
		file_put_contents($esFile, $startId);
		echo 'end:'.time()."\n";
	}
}
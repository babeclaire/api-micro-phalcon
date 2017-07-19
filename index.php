<?php
use Phalcon\Db\Adapter\Pdo\Mysql as PdoMysql;
use Phalcon\Di\FactoryDefault;
use Phalcon\Http\Response;
use Phalcon\Loader;
use Phalcon\Mvc\Micro;
$loader = new Loader();
$loader->registerDirs([
	'models',
]);
$loader->register();
$di = new FactoryDefault();
//create the conection
$di->set('db', function () {
	return new PdoMysql(
		[
			'host' => 'localhost',
			'username' => 'root',
			'password' => '',
			'dbname' => 'notice',
		]
	);
}

);
$app = new Micro($di);
// Retrieves all notices
$app->get('/notices', function () use ($app) {
	$phql = "SELECT * FROM Notice ORDER BY title";
	$notice = $app->modelsManager->executeQuery($phql);
	$data = [];
	foreach ($notice as $noti) {
		$data[] = [
			'id' => $noti->id,
			'title' => $noti->title,
			'description' => $noti->description,
			'author' => $noti->author,
			'created_date' => $noti->created_date,
		];
	}
	echo json_encode($data);
});
// Searches for notices with $title in their title
$app->get(
	'/notices/search/{title}',
	function ($title) use ($app) {
		$phql = "SELECT * FROM Notice WHERE title LIKE :title: ORDER BY title";
		$notice = $app->modelsManager->executeQuery(
			$phql,
			[
				'title' => '%' . $title . '%',

			]
		);
		$data = [];
		foreach ($notice as $noti) {
			$data[] = [
				'id' => $noti->id,
				'title' => $noti->title,
				'description' => $noti->description,
				'author' => $noti->author,
				'created_date' => $noti->created_date,
			];
		}
		echo json_encode($data);
	}
);
// Retrieves notices based on primary key
$app->get(
	'/notices/{id:[0-9]+}',
	function ($id) use ($app) {
		$phql = 'SELECT * FROM Notice WHERE id = :id:';

		$notice = $app->modelsManager->executeQuery(
			$phql,
			[
				'id' => $id,
			]
		)->getFirst();

		// Create a response
		$response = new Response();

		if ($notice === false) {
			$response->setJsonContent(
				[
					'status' => 'NOT-FOUND',
				]
			);
		} else {
			$response->setJsonContent(
				[
					'status' => 'FOUND',
					'data' => [
						'id' => $notice->id,
						'title' => $notice->title,
						'description' => $notice->description,
						'author' => $notice->author,
						'created_date' => $notice->created_date,
					],
				]
			);
		}

		return $response;
	}
);
// Adds a new notice
$app->post(
	'/notices',
	function () use ($app) {
		$notice = $app->request->getJsonRawBody();
		$phql = 'INSERT INTO Notice (title, description, author,created_date) VALUES (:title:, :description:, :author:, :created_date:)';
		$status = $app->modelsManager->executeQuery(
			$phql,
			[
				'title' => $notice->title,
				'description' => $notice->description,
				'author' => $notice->author,
				'created_date' => $notice->created_date,
			]
		);
		// Create a response
		$response = new Response();

		// Check if the insertion was successful
		if ($status->success() === true) {
			// Change the HTTP status
			$response->setStatusCode(201, 'Created');

			$notice->id = $status->getModel()->id;

			$response->setJsonContent(
				[
					'status' => 'OK',
					'data' => $notice,
				]
			);
		} else {
			// Change the HTTP status
			$response->setStatusCode(409, 'Conflict');

			// Send errors to the client
			$errors = [];

			foreach ($status->getMessages() as $message) {
				$errors[] = $message->getMessage();
			}

			$response->setJsonContent(
				[
					'status' => 'ERROR',
					'messages' => $errors,
				]
			);
		}
		return $response;
	}
);

// Updates notice based on primary key
$app->put(
	'/notices/{id:[0-9]+}',
	function ($id) use ($app) {
		$notice = $app->request->getJsonRawBody();
		$phql = 'UPDATE Notice SET title = :title:, description = :description:, author = :author: WHERE id = :id:';
		$status = $app->modelsManager->executeQuery(
			$phql,
			[
				'id' => $id,
				'title' => $notice->title,
				'description' => $notice->description,
				'author' => $notice->author,
			]
		);
		// Create a response
		$response = new Response();
		// Check if the insertion was successful
		if ($status->success() === true) {
			$response->setJsonContent(
				[
					'status' => 'OK',
				]
			);
		} else {
			// Change the HTTP status
			$response->setStatusCode(409, 'Conflict');

			$errors = [];
			foreach ($status->getMessages() as $message) {
				$errors[] = $message->getMessage();
			}
			$response->setJsonContent(
				[
					'status' => 'ERROR',
					'messages' => $errors,
				]
			);
		}
		return $response;
	}
);
// Delete notice based on primary key
$app->delete(
	'/notices/{id:[0-9]+}',
	function ($id) use ($app) {
		$phql = 'DELETE FROM Notice WHERE id = :id:';
		$status = $app->modelsManager->executeQuery(
			$phql,
			[
				'id' => $id,
			]
		);
		// Create a response
		$response = new Response();
		if ($status->success() === true) {
			$response->setJsonContent(
				[
					'status' => 'OK',
				]
			);
		} else {
			// Change the HTTP status
			$response->setStatusCode(409, 'Conflict');

			$errors = [];

			foreach ($status->getMessages() as $message) {
				$errors[] = $message->getMessage();
			}

			$response->setJsonContent(
				[
					'status' => 'ERROR',
					'messages' => $errors,
				]
			);
		}

		return $response;
	}
);
$app->handle();
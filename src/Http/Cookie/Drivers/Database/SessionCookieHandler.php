<?php

namespace Horizon\Http\Cookie\Drivers\Database;

use SessionHandlerInterface;
use SessionUpdateTimestampHandlerInterface;

class SessionCookieHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface {

	/**
	 * @var SessionCookieModel[]
	 */
	protected $models = [];

	public function open($savePath, $sessionName): bool {
		return true;
	}

	public function close(): bool {
		return true;
	}

	public function read($id): string {
		if (array_key_exists($id, $this->models)) {
			return $this->models[$id]->data;
		}

		return '';
	}

	public function write($id, $data): bool {
		$model = null;

		if (array_key_exists($id, $this->models)) {
			$model = $this->models[$id];
		}
		else {
			$model = $this->models[$id] = new SessionCookieModel();
			$model->id = $id;
		}

		$model->data = $data;
		$model->updated_at = time();
		$model->save();

		return true;
	}

	public function destroy($id): bool {
		if (array_key_exists($id, $this->models)) {
			$model = $this->models[$id];
			$model->delete();

			unset($this->models[$id]);
		}

		return true;
	}

	public function gc($maxlifetime): int {
		$expiresAt = time() - $maxlifetime;
		return SessionCookieModel::deleteFrom()->where('updated_at', '<', timestamp_to_datetime($expiresAt))->exec();
	}

	public function validateId($id): bool {
		if ($model = SessionCookieModel::where('id', '=', $id)->first()) {
			$this->models[$id] = $model;
			return true;
		}

		return false;
	}

	public function updateTimestamp($id, $data): bool {
		if (array_key_exists($id, $this->models)) {
			$model = $this->models[$id];

			if ($model->updated_at->getTimestamp() < time() - 15) {
				$model->updated_at = time();
				$model->save();
			}
		}

		return true;
	}

}

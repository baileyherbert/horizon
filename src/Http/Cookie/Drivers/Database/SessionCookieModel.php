<?php

namespace Horizon\Http\Cookie\Drivers\Database;

use DateTime;
use Horizon\Database\Model;

/**
 * @property string $id
 * @property string $data
 *
 * @property DateTime $created_at
 *
 * @property-read DateTime $updated_at
 * @property-write DateTime|int $updated_at
 */
class SessionCookieModel extends Model {

	protected $table = '@sessions';

}

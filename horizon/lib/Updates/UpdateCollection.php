<?php

namespace Horizon\Updates;

/**
 * @property-read int $length Total number of updates available.
 */
class UpdateCollection implements \Iterator
{

    private $position = 0;

    /**
     * @var Version[]
     */
    protected $versions = array();

    /**
     * Constructs a new UpdateCollection instance.
     *
     * @param Version[] $versions
     */
    public function __construct(array $versions = null)
    {
        usort($versions, function($v1, $v2) {
            return $v1->isNewerThan($v2->getVersion()) ? 1 : -1;
        });

        $this->versions = $versions;
    }

    public function __get($key)
    {
        if ($key == 'length') {
            return count($this->versions);
        }

        throw new \Exception('Undefined method UpdateCollection::' . $key . '()');
    }

    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * @return Version
     */
    public function current()
    {
        return $this->versions[$this->position];
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        $this->position++;
    }

    public function valid()
    {
        return isset($this->versions[$this->position]);
    }

}
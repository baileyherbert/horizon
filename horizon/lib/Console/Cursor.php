<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Horizon\Console;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Pierre du Plessis <pdples@gmail.com>
 */
class Cursor {
    private $output;

    public function __construct(OutputInterface $output) {
        $this->output = $output;
    }

	/**
	 * Moves up by the given number of lines.
	 *
	 * @param int $lines
	 * @return self
	 */
    public function moveUp($lines = 1) {
        $this->output->write(sprintf("\x1b[%dA", $lines));

        return $this;
    }

	/**
	 * Moves down by the given number of lines.
	 *
	 * @param int $lines
	 * @return self
	 */
    public function moveDown($lines = 1) {
        $this->output->write(sprintf("\x1b[%dB", $lines));

        return $this;
    }

	/**
	 * Moves right by the given number of columns.
	 *
	 * @param int $columns
	 * @return self
	 */
    public function moveRight($columns = 1) {
        $this->output->write(sprintf("\x1b[%dC", $columns));

        return $this;
    }

	/**
	 * Moves left by the given number of columns.
	 *
	 * @param int $columns
	 * @return self
	 */
    public function moveLeft($columns = 1) {
        $this->output->write(sprintf("\x1b[%dD", $columns));

        return $this;
    }

	/**
	 * Moves to the specified column on the current line.
	 *
	 * @param int $column
	 * @return self
	 */
    public function moveToColumn($column) {
        $this->output->write(sprintf("\x1b[%dG", $column));

        return $this;
    }

	/**
	 * Moves to the specified column and row.
	 *
	 * @param int $column
	 * @param int $row
	 * @return self
	 */
    public function moveToPosition($column, $row) {
        $this->output->write(sprintf("\x1b[%d;%dH", $row + 1, $column));

        return $this;
    }

	/**
	 * Saves the cursor position internally.
	 *
	 * @return self
	 */
    public function savePosition() {
        $this->output->write("\x1b7");

        return $this;
    }

	/**
	 * Moves the cursor back to the last saved position.
	 *
	 * @return self
	 */
    public function restorePosition() {
        $this->output->write("\x1b8");

        return $this;
    }

	/**
	 * Hides the cursor. Please be sure to `show()` it again before closing your application.
	 *
	 * @return self
	 */
    public function hide() {
        $this->output->write("\x1b[?25l");

        return $this;
    }

	/**
	 * Shows the cursor.
	 *
	 * @return self
	 */
    public function show() {
        $this->output->write("\x1b[?25h\x1b[?0c");

        return $this;
    }

    /**
     * Clears all the output from the current line.
	 *
	 * @return self
     */
    public function clearLine() {
        $this->output->write("\x1b[2K");

        return $this;
    }

    /**
     * Clears all the output from the current line after the current position.
	 *
	 * @return self
     */
    public function clearLineAfter() {
        $this->output->write("\x1b[K");

        return $this;
    }

    /**
     * Clears all the output from the cursors' current position to the end of the screen.
	 *
	 * @return self
     */
    public function clearOutput() {
        $this->output->write("\x1b[0J");

        return $this;
    }

    /**
     * Clears the entire screen.
	 *
	 * @return self
     */
    public function clearScreen() {
        $this->output->write("\x1b[2J");

        return $this;
    }
}

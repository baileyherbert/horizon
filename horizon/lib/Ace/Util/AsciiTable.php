<?php

namespace Horizon\Ace\Util;

use Exception;
use OutOfBoundsException;
use Symfony\Component\Console\Output\OutputInterface;

class AsciiTable {

	/**
	 * @var AsciiTableColumn[]
	 */
	protected $columns = array();

	/**
	 * @var mixed[]
	 */
	protected $rows = array();

	/**
	 * The character to use for the border separator between the table's header and body. Set this to a blank string
	 * to disable the border.
	 *
	 * @var string
	 */
	public $headerBorderCharacter = '=';

	/**
	 * The amount of characters to use for spacing between cells.
	 *
	 * @var int
	 */
	public $cellSpacing = 0;

	/**
	 * The text to render when there are no rows in the table.
	 *
	 * @var string
	 */
	public $noResultsText = 'nothing to show';

	/**
	 * Adds a column to the table.
	 *
	 * @param string $title
	 * @param int $minWidth
	 * @return void
	 */
	public function addColumn($title, $minWidth = 0) {
		$column = new AsciiTableColumn();
		$column->title = $title;
		$column->minWidth = $minWidth;

		$this->columns[] = $column;
	}

	/**
	 * Adds a row to the table.
	 *
	 * @param mixed[] $values
	 * @return void
	 */
	public function addRow(array $values) {
		if (count($values) !== count($this->columns)) {
			throw new Exception(
				"Row column count (" .
				count($values) .
				") does not match the table column count (" .
				count($this->columns) .
				")"
			);
		}

		foreach ($this->columns as $index => $column) {
			if (!isset($values[$index])) {
				throw new Exception("Row is missing index [$index]");
			}
		}

		$this->rows[] = $values;
	}

	/**
	 * Returns the calculated width of the column at the specified index (zero-based).
	 *
	 * @param int $index
	 * @return int
	 */
	public function getColumnWidth($index) {
		if (!isset($this->columns[$index])) {
			throw new OutOfBoundsException("Column index out of bounds");
		}

		$column = $this->columns[$index];
		$largestWidth = max(0, $column->minWidth, $this->getTextWidth($column->title));

		foreach ($this->rows as $row) {
			$colWidth = $this->getTextWidth($row[$index]);

			if ($colWidth > $largestWidth) {
				$largestWidth = $colWidth;
			}
		}

		if ($index < count($this->columns) - 1) {
			$largestWidth += $this->cellSpacing;
		}

		return $largestWidth;
	}

	/**
	 * Returns the width of the table.
	 *
	 * @return int
	 */
	public function getWidth() {
		$total = 0;

		foreach ($this->columns as $index => $column) {
			$total += $this->getColumnWidth($index);
		}

		if (empty($this->rows)) {
			$total = max($total, $this->getTextWidth($this->noResultsText));
		}

		return $total;
	}

	/**
	 * Renders the table to the given output stream.
	 *
	 * @param OutputInterface $out
	 * @return void
	 */
	public function render(OutputInterface $out) {
		$this->renderHeader($out);
		$this->renderHeaderBorder($out);
		$this->renderRows($out);
	}

	/**
	 * Renders all columns of the table header.
	 *
	 * @param OutputInterface $out
	 * @return void
	 */
	protected function renderHeader(OutputInterface $out) {
		foreach ($this->columns as $index => $column) {
			$titleWidth = $this->getTextWidth($column->title);
			$columnWidth = $this->getColumnWidth($index);
			$padding = str_repeat(' ', $columnWidth - $titleWidth);

			$out->write($column->title . $padding);
		}

		$out->writeln('');
	}

	/**
	 * Renders the border line between the header and body.
	 *
	 * @param OutputInterface $out
	 * @return void
	 */
	protected function renderHeaderBorder(OutputInterface $out) {
		if (!empty($this->headerBorderCharacter)) {
			$out->writeln(str_repeat($this->headerBorderCharacter, $this->getWidth()));
		}
	}

	/**
	 * Renders the rows in the table.
	 *
	 * @param OutputInterface $out
	 * @return void
	 */
	protected function renderRows(OutputInterface $out) {
		foreach ($this->rows as $row) {
			foreach ($this->columns as $index => $column) {
				$value = $row[$index];
				$valueWidth = $this->getTextWidth($value);
				$columnWidth = $this->getColumnWidth($index);
				$padding = str_repeat(' ', $columnWidth - $valueWidth);

				$out->write($value . $padding);
			}

			$out->writeln('');
		}

		if (empty($this->rows)) {
			$out->writeln($this->noResultsText);
		}
	}

	/**
	 * Returns the width of the given text while accounting for Symfony formatting.
	 *
	 * @param string $text
	 * @return int
	 */
	protected function getTextWidth($text) {
		$text = preg_replace("/<(?:fg|bg)=(?:[^>]+)>(.+?)<\/>/", "$1", $text);
		return mb_strlen($text);
	}

}

class AsciiTableColumn {
	/**
	 * @var string
	 */
	public $title;

	/**
	 * @var int
	 */
	public $minWidth;
}

<?php

namespace Horizon\View;

use Horizon\Foundation\Application;

class Component
{

	/**
	 * Returns the context for the current environment. If the component is called from outside of a view, the returned
	 * array will always be empty.
	 *
	 * @return array
	 */
	protected function getContext()
	{
		return Application::kernel()->view()->getContext();
	}

}

<?php

declare(strict_types=1);

namespace Acme;

/**
 * Identifies the widget lifecycle state.
 */
enum WidgetStatus: string
{
	/** Widget accepts new work. */
	case Ready = 'ready';

	/** Widget has finished its work. */
	case Complete = 'complete';
}

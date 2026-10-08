import { __ } from '@wordpress/i18n';
import { RangeControl } from '@wordpress/components';

import { CACHE_TTL } from './attributes';

export default function CacheTtlControl( { value, onChange } ) {
	return (
		<RangeControl
			__nextHasNoMarginBottom
			__next40pxDefaultSize
			label={ __( 'Cache lifetime (seconds)', 'cached-query-blocks' ) }
			help={ __(
				'Content changes clear the cache sooner. This is the upper bound.',
				'cached-query-blocks'
			) }
			value={ value }
			onChange={ onChange }
			min={ CACHE_TTL.min }
			max={ CACHE_TTL.max }
			step={ 60 }
		/>
	);
}

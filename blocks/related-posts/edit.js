import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl, ToggleControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

import metadata from './block.json';
import { normalizeAttributes, POSTS_PER_PAGE } from '../shared/attributes';
import CacheTtlControl from '../shared/cache-ttl-control';

export default function Edit( { attributes, setAttributes, context } ) {
	const { postsPerPage, showExcerpt, cacheTtl } = attributes;

	const update = ( changes ) =>
		setAttributes( normalizeAttributes( changes ) );

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'cached-query-blocks' ) }>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Number of posts', 'cached-query-blocks' ) }
						value={ postsPerPage }
						onChange={ ( value ) =>
							update( { postsPerPage: value } )
						}
						min={ POSTS_PER_PAGE.min }
						max={ POSTS_PER_PAGE.max }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show excerpt', 'cached-query-blocks' ) }
						checked={ showExcerpt }
						onChange={ ( value ) =>
							update( { showExcerpt: value } )
						}
					/>
					<CacheTtlControl
						value={ cacheTtl }
						onChange={ ( value ) => update( { cacheTtl: value } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<ServerSideRender
				block={ metadata.name }
				attributes={ attributes }
				urlQueryArgs={ { post_id: context.postId } }
				EmptyResponsePlaceholder={ () => (
					<p>
						{ __(
							'No related posts yet. They appear once this post shares a term with other published posts.',
							'cached-query-blocks'
						) }
					</p>
				) }
			/>
		</div>
	);
}

import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import ServerSideRender from '@wordpress/server-side-render';

import metadata from './block.json';
import { normalizeAttributes, POSTS_PER_PAGE } from '../shared/attributes';
import CacheTtlControl from '../shared/cache-ttl-control';

export default function Edit( { attributes, setAttributes } ) {
	const { postType, taxonomy, termId, postsPerPage, showExcerpt, cacheTtl } =
		attributes;

	const update = ( changes ) =>
		setAttributes( normalizeAttributes( changes ) );

	const { postTypes, taxonomies, terms } = useSelect(
		( select ) => {
			const core = select( coreStore );

			return {
				postTypes: core.getPostTypes( { per_page: -1 } ) ?? [],
				taxonomies:
					core.getTaxonomies( { type: postType, per_page: -1 } ) ??
					[],
				terms: taxonomy
					? core.getEntityRecords( 'taxonomy', taxonomy, {
							per_page: 100,
							_fields: 'id,name',
					  } ) ?? []
					: [],
			};
		},
		[ postType, taxonomy ]
	);

	const postTypeOptions = postTypes
		.filter( ( type ) => type.viewable && type.slug !== 'attachment' )
		.map( ( type ) => ( { label: type.name, value: type.slug } ) );

	const taxonomyOptions = [
		{ label: __( 'Any', 'cached-query-blocks' ), value: '' },
		...taxonomies
			.filter( ( tax ) => tax.visibility?.public )
			.map( ( tax ) => ( { label: tax.name, value: tax.slug } ) ),
	];

	const termOptions = [
		{ label: __( 'Any', 'cached-query-blocks' ), value: '0' },
		...terms.map( ( term ) => ( {
			label: term.name,
			value: String( term.id ),
		} ) ),
	];

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody title={ __( 'Query', 'cached-query-blocks' ) }>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Post type', 'cached-query-blocks' ) }
						value={ postType }
						options={ postTypeOptions }
						onChange={ ( value ) =>
							update( {
								postType: value,
								taxonomy: '',
								termId: 0,
							} )
						}
					/>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Taxonomy', 'cached-query-blocks' ) }
						value={ taxonomy }
						options={ taxonomyOptions }
						onChange={ ( value ) =>
							update( { taxonomy: value, termId: 0 } )
						}
					/>
					{ taxonomy && (
						<SelectControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Term', 'cached-query-blocks' ) }
							value={ String( termId ) }
							options={ termOptions }
							onChange={ ( value ) =>
								update( { termId: value } )
							}
						/>
					) }
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
			/>
		</div>
	);
}

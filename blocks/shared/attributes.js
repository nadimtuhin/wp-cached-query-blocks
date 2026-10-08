export const POSTS_PER_PAGE = { min: 1, max: 20, fallback: 5 };
export const CACHE_TTL = { min: 60, max: 86400, fallback: 600 };

function clampInteger( value, { min, max, fallback } ) {
	const number = Number.parseInt( value, 10 );

	if ( Number.isNaN( number ) ) {
		return fallback;
	}

	return Math.min( max, Math.max( min, number ) );
}

/**
 * Mirrors the server-side clamping so the editor never stores values the PHP side would rewrite.
 *
 * @param {Object} attributes Partial block attributes.
 * @return {Object} The same attributes with numeric values clamped.
 */
export function normalizeAttributes( attributes ) {
	const normalized = { ...attributes };

	if ( 'postsPerPage' in normalized ) {
		normalized.postsPerPage = clampInteger(
			normalized.postsPerPage,
			POSTS_PER_PAGE
		);
	}

	if ( 'cacheTtl' in normalized ) {
		normalized.cacheTtl = clampInteger( normalized.cacheTtl, CACHE_TTL );
	}

	if ( 'termId' in normalized ) {
		normalized.termId = Math.max(
			0,
			Number.parseInt( normalized.termId, 10 ) || 0
		);
	}

	return normalized;
}

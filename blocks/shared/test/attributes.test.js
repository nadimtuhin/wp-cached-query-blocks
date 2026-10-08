import { normalizeAttributes } from '../attributes';

describe( 'normalizeAttributes', () => {
	it( 'clamps postsPerPage to 1..20', () => {
		expect( normalizeAttributes( { postsPerPage: 0 } ).postsPerPage ).toBe(
			1
		);
		expect( normalizeAttributes( { postsPerPage: 99 } ).postsPerPage ).toBe(
			20
		);
	} );

	it( 'clamps cacheTtl to 60..86400', () => {
		expect( normalizeAttributes( { cacheTtl: 5 } ).cacheTtl ).toBe( 60 );
		expect( normalizeAttributes( { cacheTtl: 1e9 } ).cacheTtl ).toBe(
			86400
		);
	} );

	it( 'falls back to defaults for non-numeric input', () => {
		expect(
			normalizeAttributes( { postsPerPage: 'x' } ).postsPerPage
		).toBe( 5 );
		expect( normalizeAttributes( { cacheTtl: '' } ).cacheTtl ).toBe( 600 );
		expect( normalizeAttributes( { termId: 'abc' } ).termId ).toBe( 0 );
	} );

	it( 'leaves other attributes untouched and does not add missing ones', () => {
		expect( normalizeAttributes( { showExcerpt: true } ) ).toEqual( {
			showExcerpt: true,
		} );
	} );
} );

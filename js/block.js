// Import styles
import './style.scss';

import edit from './edit';

const {registerBlockType} = wp.blocks;
const {InnerBlocks} = wp.blockEditor;

registerBlockType('bsmaps/bsmap', {
    title: 'Map',
    icon: 'universal-access-alt',
    apiVersion: 2,
    category: 'common',
    supports: { align: ['full']},
    attributes: {
        bgImageId: {
            type: 'number',
        },
    },
    edit,
    save() {
        return (
            <InnerBlocks.Content />
        );
    }
});

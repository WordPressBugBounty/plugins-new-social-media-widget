(function (blocks, element, serverSideRender, blockEditor, components) {
    var el = element.createElement;
    var InspectorControls = blockEditor.InspectorControls;
    var useBlockProps = blockEditor.useBlockProps;
    var TextControl = components.TextControl;
    var SelectControl = components.SelectControl;
    var PanelBody = components.PanelBody;
    var RangeControl = components.RangeControl;
    var PanelColorSettings = blockEditor.PanelColorSettings;
    var ServerSideRender = serverSideRender;
    var Button = components.Button;

    var socialKeys = [
        'facebook', 'x-twitter', 'linkedin', 'instagram',
        'pinterest', 'flickr', 'tumblr', 'youtube',
        'vimeo', 'rss', 'envelope', 'whatsapp'
    ];

    var blockAttributes = {
        title: { type: 'string', default: '' },
        columns: { type: 'string', default: 'col-md-2' },
        icon_size: { type: 'string', default: '2' },
        padding: { type: 'string', default: '0' },
        background: { type: 'string', default: '' },
        div_bg_color: { type: 'string', default: '#dd3333' },
        icon_color: { type: 'string', default: '#ffffff' },
        effect_type: { type: 'string', default: 'none' },
        hover_effects: { type: 'string', default: '' },
        css: { type: 'string', default: '' },
        url_target: { type: 'string', default: '_new' },
        // Array attribute to hold the repeater data
        profiles: {
            type: 'array',
            default: []
        }
    };

    // Keep legacy attributes for backwards compatibility so existing blocks don't break
    socialKeys.forEach(function (key) {
        blockAttributes[key] = { type: 'string', default: '' };
    });

    blocks.registerBlockType('nsmw/social-media-icons', {
        apiVersion: 3,
        title: 'Social Media Widget',
        icon: 'share',
        category: 'widgets',
        attributes: blockAttributes,

        // Migration from Legacy Widget:
        transforms: {
            from: [
                {
                    type: 'block',
                    blocks: ['core/legacy-widget'],
                    isMatch: function (attributes) {
                        return attributes.idBase === 'new_social_media_widget';
                    },
                    transform: function (attributes) {
                        var instance = attributes.instance.raw || {};
                        var newAttributes = {};

                        // Map standard attributes
                        Object.keys(blockAttributes).forEach(function (key) {
                            if (instance[key] !== undefined && key !== 'profiles' && socialKeys.indexOf(key) === -1) {
                                newAttributes[key] = instance[key];
                            }
                        });

                        // Handle profiles array migration
                        if (instance.profiles && Array.isArray(instance.profiles)) {
                            newAttributes.profiles = instance.profiles;
                        } else {
                            // Map legacy individual fields to profiles array
                            var migratedProfiles = [];
                            socialKeys.forEach(function (key) {
                                var val = instance[key] || instance[key + ' '];
                                if (val) {
                                    migratedProfiles.push({ network: key, url: val });
                                }
                            });
                            newAttributes.profiles = migratedProfiles;
                        }

                        return blocks.createBlock('nsmw/social-media-icons', newAttributes);
                    }
                }
            ]
        },

        edit: function (props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            // Optional: Component mount migration hook mapping legacy attributes to `profiles`
            // if profiles is empty but legacy attrs exist (for blocks placed BEFORE this update)
            if (attributes.profiles.length === 0) {
                var hasLegacy = false;
                var mappedProfiles = [];
                socialKeys.forEach(function (key) {
                    if (attributes[key] && attributes[key] !== '') {
                        hasLegacy = true;
                        mappedProfiles.push({ network: key, url: attributes[key] });
                    }
                });
                if (hasLegacy) {
                    setTimeout(function () {
                        setAttributes({ profiles: mappedProfiles });
                    }, 0);
                }
            }

            var updateProfile = function (index, newProfile) {
                var newProfiles = attributes.profiles.slice();
                newProfiles[index] = newProfile;
                setAttributes({ profiles: newProfiles });
            };

            var removeProfile = function (index) {
                var newProfiles = attributes.profiles.slice();
                newProfiles.splice(index, 1);
                setAttributes({ profiles: newProfiles });
            };

            var addProfile = function () {
                var newProfiles = attributes.profiles.slice();
                newProfiles.push({ network: 'facebook', url: '' });
                setAttributes({ profiles: newProfiles });
            };

            var networkOptions = socialKeys.map(function (key) {
                return { label: key.replace(/-/g, ' ').toUpperCase(), value: key };
            });

            var repeaterUI = attributes.profiles.map(function (profile, index) {
                return el(
                    'div',
                    {
                        draggable: true,
                        onDragStart: function (e) {
                            e.dataTransfer.effectAllowed = 'move';
                            e.dataTransfer.setData('text/plain', index);
                        },
                        onDragOver: function (e) {
                            e.preventDefault();
                            e.dataTransfer.dropEffect = 'move';
                        },
                        onDrop: function (e) {
                            e.preventDefault();
                            var fromIndex = parseInt(e.dataTransfer.getData('text/plain'), 10);
                            var toIndex = index;
                            if (fromIndex !== toIndex && !isNaN(fromIndex)) {
                                var newProfiles = attributes.profiles.slice();
                                var movedItem = newProfiles.splice(fromIndex, 1)[0];
                                newProfiles.splice(toIndex, 0, movedItem);
                                setAttributes({ profiles: newProfiles });
                            }
                        },
                        style: { padding: '8px', background: '#f9f9f9', marginBottom: '10px', marginLeft: '-11px', marginRight: '-11px', border: '1px solid #ddd', cursor: 'move', display: 'flex', gap: '8px', alignItems: 'center' }
                    },
                    el('span', { className: 'dashicons dashicons-menu', style: { color: '#888' } }),
                    el('div', { style: { width: '160px' } },
                        el(SelectControl, {
                            value: profile.network,
                            options: networkOptions,
                            onChange: function (val) { updateProfile(index, { network: val, url: profile.url }); },
                            __nextHasNoMarginBottom: true
                        })
                    ),
                    el('div', { style: { flexGrow: 1, minWidth: 0 } },
                        el(TextControl, {
                            value: profile.url,
                            placeholder: 'https://...',
                            onChange: function (val) { updateProfile(index, { network: profile.network, url: val }); },
                            __nextHasNoMarginBottom: true
                        })
                    ),
                    el(Button, {
                        isDestructive: true,
                        isSmall: true,
                        icon: 'no-alt',
                        onClick: function () { removeProfile(index); },
                        style: { padding: '4px' }
                    })
                );
            });

            var inspectorControls = el(
                InspectorControls,
                {},
                el(
                    PanelBody,
                    { title: 'Social Profiles', initialOpen: true },
                    repeaterUI,
                    el(Button, {
                        isPrimary: true,
                        style: { width: '100%', justifyContent: 'center' },
                        onClick: addProfile
                    }, '+ Add Social Icon')
                ),
                el(
                    PanelBody,
                    { title: 'Display Settings', initialOpen: false },
                    el(SelectControl, {
                        label: 'Columns',
                        value: attributes.columns,
                        options: [
                            { label: '1 Column', value: 'col-md-12' },
                            { label: '2 Columns', value: 'col-md-6' },
                            { label: '3 Columns', value: 'col-md-4' },
                            { label: '4 Columns', value: 'col-md-3' },
                            { label: '6 Columns', value: 'col-md-2' },
                            { label: '12 Columns', value: 'col-md-1' }
                        ],
                        onChange: function (val) { setAttributes({ columns: val }); }
                    }),
                    el(SelectControl, {
                        label: 'Icon Size',
                        value: attributes.icon_size,
                        options: [
                            { label: '1x', value: '1' },
                            { label: '2x', value: '2' },
                            { label: '3x', value: '3' },
                            { label: '4x', value: '4' },
                            { label: '5x', value: '5' }
                        ],
                        onChange: function (val) { setAttributes({ icon_size: val }); }
                    }),
                    el(SelectControl, {
                        label: 'Open links in',
                        value: attributes.url_target,
                        options: [
                            { label: 'New Tab', value: '_new' },
                            { label: 'Same Window', value: '_self' }
                        ],
                        onChange: function (val) { setAttributes({ url_target: val }); }
                    }),
                    el(RangeControl, {
                        label: 'Padding (px)',
                        value: attributes.padding !== undefined ? parseInt(attributes.padding, 10) : 0,
                        min: 0,
                        max: 50,
                        onChange: function (val) { setAttributes({ padding: val.toString() }); }
                    }),
                    el(
                        PanelColorSettings,
                        {
                            title: 'Colors',
                            initialOpen: true,
                            colorSettings: [
                                {
                                    value: attributes.div_bg_color,
                                    onChange: function (val) { setAttributes({ div_bg_color: val || '#dd3333' }); },
                                    label: 'Background Color'
                                },
                                {
                                    value: attributes.icon_color,
                                    onChange: function (val) { setAttributes({ icon_color: val || '#ffffff' }); },
                                    label: 'Icon Color'
                                }
                            ]
                        }
                    )
                ),
                el(
                    PanelBody,
                    { title: 'Effects', initialOpen: false },
                    el(SelectControl, {
                        label: 'Effect Type',
                        value: attributes.effect_type,
                        options: [
                            { label: 'None', value: 'none' },
                            { label: 'Hover CSS', value: 'hover' }
                        ],
                        onChange: function (val) { setAttributes({ effect_type: val }); }
                    }),
                    attributes.effect_type === 'hover' && el(SelectControl, {
                        label: 'Hover Effect Class',
                        value: attributes.hover_effects,
                        options: [
                            { label: 'None', value: '' },
                            { label: 'Shadow', value: 'hvr-shadow' },
                            { label: 'Grow Shadow', value: 'hvr-grow-shadow' },
                            { label: 'Float Shadow', value: 'hvr-float-shadow' },
                            { label: 'Glow', value: 'hvr-glow' },
                            { label: 'Shadow Radial', value: 'hvr-shadow-radial' },
                            { label: 'Box Shadow Outset', value: 'hvr-box-shadow-outset' },
                            { label: 'Box Shadow Inset', value: 'hvr-box-shadow-inset' }
                        ],
                        onChange: function (val) { setAttributes({ hover_effects: val }); }
                    })
                ),
                el(
                    PanelBody,
                    { title: '🔥 Pro Features & Support', initialOpen: false },
                    el('p', { style: { fontWeight: '600', color: '#856404', marginBottom: '12px' } }, 'Upgrade to Pro for more power:'),
                    el('ul', { style: { paddingLeft: '0', listStyleType: 'none', marginBottom: '20px' } },
                        el('li', { style: { marginBottom: '8px', display: 'flex', alignItems: 'center', gap: '8px' } }, el('span', { className: 'dashicons dashicons-yes', style: { color: '#28a745' } }), '30+ Pro Social Networks'),
                        el('li', { style: { marginBottom: '8px', display: 'flex', alignItems: 'center', gap: '8px' } }, el('span', { className: 'dashicons dashicons-yes', style: { color: '#28a745' } }), 'Custom SVG Icon Uploads'),
                        el('li', { style: { marginBottom: '8px', display: 'flex', alignItems: 'center', gap: '8px' } }, el('span', { className: 'dashicons dashicons-yes', style: { color: '#28a745' } }), '20+ Extra Hover Animations'),
                        el('li', { style: { marginBottom: '8px', display: 'flex', alignItems: 'center', gap: '8px' } }, el('span', { className: 'dashicons dashicons-yes', style: { color: '#28a745' } }), '3+ Pre-built Professional Styles'),
                        el('li', { style: { marginBottom: '8px', display: 'flex', alignItems: 'center', gap: '8px' } }, el('span', { className: 'dashicons dashicons-yes', style: { color: '#28a745' } }), 'Advanced Multi-column Layouts'),
                        el('li', { style: { marginBottom: '8px', display: 'flex', alignItems: 'center', gap: '8px' } }, el('span', { className: 'dashicons dashicons-yes', style: { color: '#28a745' } }), 'Priority Email Support')
                    ),
                    el('div', { style: { display: 'flex', flexDirection: 'column', gap: '10px' } },
                        el(Button, {
                            variant: 'secondary',
                            href: 'https://awplife.com/demo/social-media-widget-premium/',
                            target: '_blank',
                            style: { width: '100%', justifyContent: 'center' }
                        }, 'Check Pro Live Demo'),
                        el(Button, {
                            variant: 'primary',
                            href: 'https://awplife.com/wordpress-plugins/social-media-widget-wordpress-plugin/',
                            target: '_blank',
                            style: { width: '100%', justifyContent: 'center', background: '#ff4d4d', borderColor: '#ff3333' }
                        }, 'Buy Pro Plugin')
                    ),
                    el('p', { style: { marginTop: '15px', fontSize: '11px', textAlign: 'center', color: '#666', fontStyle: 'italic' } }, 'Unlock more features today!')
                )
            );

            // useBlockProps provides all necessary props for block selection/focus
            var blockProps = useBlockProps({
                onClickCapture: function (e) {
                    // Prevent any <a> link inside the preview from navigating away
                    var target = e.target;
                    while (target && target !== e.currentTarget) {
                        if (target.tagName === 'A') {
                            e.preventDefault();
                            return;
                        }
                        target = target.parentNode;
                    }
                }
            });

            return el(
                'div',
                blockProps,
                inspectorControls,
                el('div', { style: { pointerEvents: 'none' } },
                    el(ServerSideRender, {
                        block: 'nsmw/social-media-icons',
                        attributes: Object.assign({}, attributes, {})
                    })
                )
            );
        },

        save: function () {
            // ServerSideRender blocks return null for save
            return null;
        }
    });

})(window.wp.blocks, window.wp.element, window.wp.serverSideRender, window.wp.blockEditor, window.wp.components);

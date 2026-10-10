(function () {
    'use strict';

    var layout = JSON.parse(document.getElementById('layout-data').textContent);
    var sample = JSON.parse(document.getElementById('sample-data').textContent);
    var cfg = JSON.parse(document.getElementById('design-config').textContent);

    var stage = document.getElementById('stage');
    var stageBg = document.getElementById('stage-bg');
    var layer = document.getElementById('layer');
    var props = document.getElementById('props');
    var elementsList = document.getElementById('elements-list');
    var faceTabs = document.getElementById('face-tabs');

    var TEMPLATE_WIDTH = 2596;
    var currentFace = 'frontal';
    var selectedId = null;
    var nodes = {};
    var drag = null;

    function face() { return layout.faces[currentFace]; }
    function findEl(id) { return face().elements.filter(function (e) { return e.id === id; })[0]; }
    function round(n) { return Math.round(n * 10000) / 10000; }
    function fontScale() { return stage.offsetWidth / TEMPLATE_WIDTH; }

    function loadFonts() {
        if (!cfg.font) return;
        var faces = [
            { family: 'Karvest', file: 'karvest', weight: 400 },
            { family: 'Karvest', file: 'karvest_bold', weight: 700 },
            { family: 'Georama', file: 'georama', weight: 400 },
            { family: 'Georama', file: 'georama_bold', weight: 700 }
        ];
        var css = faces.map(function (f) {
            return "@font-face{font-family:'" + f.family + "';src:url('"
                + cfg.font.replace('{font}', f.file) + "');font-weight:" + f.weight + ";font-style:normal;}";
        }).join('');
        var style = document.createElement('style');
        style.textContent = css;
        document.head.appendChild(style);
    }

    function sampleText(bind) {
        switch (bind) {
            case 'nombres': return sample.nombres + ' ' + sample.apellidos;
            case 'cedula':
                var d = String(sample.cedula).replace(/\D/g, '');
                return 'C.I. V-' + d.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            case 'cargo': return sample.cargo;
            case 'dependencia': return sample.dependencia;
            case 'codigo': return sample.codigo;
            default: return '';
        }
    }

    function elementText(el) { return el.text || sampleText(el.bind); }

    function positionNode(el) {
        var node = nodes[el.id];
        if (!node) return;
        node.style.left = (el.x * 100) + '%';
        node.style.top = (el.y * 100) + '%';
        var tx = '-50%';
        if (el.kind === 'text') {
            if ((el.align || 'center') === 'left') tx = '0';
            else if ((el.align || 'center') === 'right') tx = '-100%';
        }
        node.style.transform = 'translate(' + tx + ', -50%)';
    }

    function styleNode(el) {
        var node = nodes[el.id];
        if (!node) return;
        node.className = 'stage-el kind-' + el.kind
            + (el.enabled === false ? ' disabled' : '')
            + (el.id === selectedId ? ' selected' : '');
        positionNode(el);

        if (el.kind === 'text') {
            node.textContent = elementText(el) || '(vacío)';
            node.style.color = el.color || '#000';
            node.style.fontSize = (el.size * fontScale()) + 'px';
            node.style.textAlign = el.align || 'center';
            node.style.fontFamily = el.font === 'karvest' ? "'Karvest', Georgia, serif" : "'Georama', Arial, sans-serif";
            node.style.fontWeight = el.bold ? '700' : '400';
            node.style.width = el.max_width ? (el.max_width * 100) + '%' : 'auto';
        } else {
            node.style.width = (el.w * 100) + '%';
            node.style.height = (el.h * 100) + '%';
            node.style.borderRadius = el.kind === 'image' && el.radius ? (el.radius * stage.offsetWidth) + 'px' : '0';
            node.style.border = el.kind === 'image' && el.border_width
                ? el.border_width + 'px solid ' + (el.border_color || '#fff') : 'none';
            node.textContent = el.kind === 'qrcode' ? 'QR' : 'FOTO';
        }
    }

    function renderStage() {
        layer.innerHTML = '';
        nodes = {};
        stageBg.src = cfg.background.replace('{face}', currentFace);

        face().elements.forEach(function (el) {
            var node = document.createElement('div');
            node.dataset.id = el.id;
            node.addEventListener('pointerdown', onPointerDown);
            layer.appendChild(node);
            nodes[el.id] = node;
            styleNode(el);
        });

        renderElementsList();
    }

    function renderElementsList() {
        elementsList.innerHTML = '';
        face().elements.forEach(function (el) {
            var row = document.createElement('div');
            row.className = 'el-row' + (el.id === selectedId ? ' active' : '');

            var toggle = document.createElement('input');
            toggle.type = 'checkbox';
            toggle.checked = el.enabled !== false;
            toggle.addEventListener('change', function () {
                el.enabled = toggle.checked;
                styleNode(el);
            });

            var label = document.createElement('span');
            label.textContent = el.id + ' (' + el.kind + ')';
            label.addEventListener('click', function () { select(el.id); });

            row.appendChild(toggle);
            row.appendChild(label);
            elementsList.appendChild(row);
        });
    }

    function select(id) {
        selectedId = id;
        Object.keys(nodes).forEach(function (key) {
            nodes[key].classList.toggle('selected', key === id);
        });
        renderElementsList();
        buildProps(findEl(id));
    }

    function field(label, input) {
        var wrap = document.createElement('label');
        wrap.className = 'prop-field';
        var span = document.createElement('span');
        span.textContent = label;
        wrap.appendChild(span);
        wrap.appendChild(input);
        return wrap;
    }

    function numberInput(value, step, onInput) {
        var input = document.createElement('input');
        input.type = 'number';
        input.step = step;
        input.value = value;
        input.addEventListener('input', function () {
            onInput(parseFloat(input.value));
        });
        return input;
    }

    function buildProps(el) {
        if (!el) {
            props.innerHTML = '<p class="empty">Selecciona un elemento para editarlo.</p>';
            return;
        }

        props.innerHTML = '';
        var title = document.createElement('h3');
        title.textContent = el.id;
        props.appendChild(title);

        var enabled = document.createElement('input');
        enabled.type = 'checkbox';
        enabled.checked = el.enabled !== false;
        enabled.addEventListener('change', function () {
            el.enabled = enabled.checked;
            styleNode(el);
        });
        props.appendChild(field('Visible', enabled));

        props.appendChild(field('X (%)', numberInput(Math.round(el.x * 1000) / 10, 0.1, function (v) {
            el.x = round(v / 100); positionNode(el);
        })));
        props.appendChild(field('Y (%)', numberInput(Math.round(el.y * 1000) / 10, 0.1, function (v) {
            el.y = round(v / 100); positionNode(el);
        })));

        if (el.kind === 'text') {
            props.appendChild(field('Tamaño (px)', numberInput(el.size, 1, function (v) {
                el.size = v; styleNode(el);
            })));

            var color = document.createElement('input');
            color.type = 'color';
            color.value = el.color || '#000000';
            color.addEventListener('input', function () { el.color = color.value; styleNode(el); });
            props.appendChild(field('Color', color));

            var font = document.createElement('select');
            ['georama', 'karvest'].forEach(function (f) {
                var o = document.createElement('option');
                o.value = f; o.textContent = f;
                o.selected = (el.font || 'karvest') === f;
                font.appendChild(o);
            });
            font.addEventListener('change', function () { el.font = font.value; styleNode(el); });
            props.appendChild(field('Fuente', font));

            var align = document.createElement('select');
            ['center', 'left', 'right'].forEach(function (a) {
                var o = document.createElement('option');
                o.value = a; o.textContent = a;
                o.selected = (el.align || 'center') === a;
                align.appendChild(o);
            });
            align.addEventListener('change', function () { el.align = align.value; styleNode(el); });
            props.appendChild(field('Alineación', align));

            props.appendChild(field('Ancho máx. (%)', numberInput(Math.round((el.max_width || 0) * 1000) / 10, 0.1, function (v) {
                el.max_width = round(v / 100); styleNode(el);
            })));

            var bind = document.createElement('input');
            bind.type = 'text';
            bind.value = el.bind || el.text || '';
            bind.disabled = true;
            props.appendChild(field('Dato', bind));
        } else {
            props.appendChild(field('Ancho (%)', numberInput(Math.round(el.w * 1000) / 10, 0.1, function (v) {
                el.w = round(v / 100); styleNode(el);
            })));
            props.appendChild(field('Alto (%)', numberInput(Math.round(el.h * 1000) / 10, 0.1, function (v) {
                el.h = round(v / 100); styleNode(el);
            })));

            if (el.kind === 'image') {
                props.appendChild(field('Radio (%)', numberInput(Math.round((el.radius || 0) * 1000) / 10, 0.1, function (v) {
                    el.radius = round(v / 100); styleNode(el);
                })));
                props.appendChild(field('Borde (px)', numberInput(el.border_width || 0, 1, function (v) {
                    el.border_width = v; styleNode(el);
                })));
                var bc = document.createElement('input');
                bc.type = 'color';
                bc.value = el.border_color || '#ffffff';
                bc.addEventListener('input', function () { el.border_color = bc.value; styleNode(el); });
                props.appendChild(field('Color borde', bc));
            }
        }
    }

    function onPointerDown(e) {
        var id = e.currentTarget.dataset.id;
        select(id);
        drag = { id: id, rect: stage.getBoundingClientRect() };
        e.currentTarget.setPointerCapture(e.pointerId);
        e.preventDefault();
    }

    window.addEventListener('pointermove', function (e) {
        if (!drag) return;
        var el = findEl(drag.id);
        var rect = drag.rect;
        var nx = (e.clientX - rect.left) / rect.width;
        var ny = (e.clientY - rect.top) / rect.height;
        el.x = round(Math.min(1, Math.max(0, nx)));
        el.y = round(Math.min(1, Math.max(0, ny)));
        positionNode(el);
        buildPropsQuickXY(el);
    });

    function buildPropsQuickXY(el) {
        var inputs = props.querySelectorAll('input[type=number]');
        if (inputs.length >= 2) {
            inputs[0].value = Math.round(el.x * 1000) / 10;
            inputs[1].value = Math.round(el.y * 1000) / 10;
        }
    }

    window.addEventListener('pointerup', function () { drag = null; });

    faceTabs.addEventListener('click', function (e) {
        var btn = e.target.closest('button[data-face]');
        if (!btn) return;
        currentFace = btn.dataset.face;
        selectedId = null;
        Array.prototype.forEach.call(faceTabs.children, function (b) {
            b.classList.toggle('active', b.dataset.face === currentFace);
        });
        renderStage();
        buildProps(null);
    });

    document.getElementById('btn-save').addEventListener('click', function () {
        document.getElementById('layout-input').value = JSON.stringify(layout);
        document.getElementById('save-form').submit();
    });

    document.getElementById('btn-restore').addEventListener('click', function () {
        if (!confirm('¿Descartar los cambios no guardados y volver al diseño guardado?')) {
            return;
        }
        fetch(cfg.current, { credentials: 'same-origin' })
            .then(function (r) {
                if (!r.ok) throw new Error('No se pudo cargar el diseño guardado');
                return r.json();
            })
            .then(function (data) {
                layout = data;
                currentFace = 'frontal';
                selectedId = null;
                Array.prototype.forEach.call(faceTabs.children, function (b) {
                    b.classList.toggle('active', b.dataset.face === 'frontal');
                });
                renderStage();
                buildProps(null);
            })
            .catch(function (err) { alert(err.message); });
    });

    var modal = document.getElementById('preview-modal');
    var previewImg = document.getElementById('preview-img');

    document.getElementById('btn-preview').addEventListener('click', function () {
        var body = new FormData();
        body.append('layout', JSON.stringify(layout));
        body.append('face', currentFace);
        body.append('_token', cfg.token);

        fetch(cfg.preview, { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (r) {
                if (!r.ok) throw new Error('No se pudo generar la vista previa');
                return r.blob();
            })
            .then(function (blob) {
                previewImg.src = URL.createObjectURL(blob);
                modal.classList.remove('hidden');
            })
            .catch(function (err) { alert(err.message); });
    });

    document.getElementById('preview-close').addEventListener('click', function () {
        modal.classList.add('hidden');
    });
    modal.addEventListener('click', function (e) {
        if (e.target === modal) modal.classList.add('hidden');
    });

    window.addEventListener('resize', function () {
        face().elements.forEach(styleNode);
    });

    loadFonts();
    renderStage();
})();

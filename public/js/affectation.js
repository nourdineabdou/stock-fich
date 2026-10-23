// Composants Alpine.js pour l'affectation poissons -> chariots -> tunnels et le cartonnage.

function fmtKg(n) {
    return Number(n || 0).toLocaleString('fr-FR', { maximumFractionDigits: 2 });
}

// Etape 1 : choix des chariots avec jauge de capacite
function cartPicker(carts, preselected, target) {
    return {
        carts,
        target,
        selected: preselected.filter(id => carts.some(c => c.id === id)),
        search: '',
        tunnel: '',
        fmt: fmtKg,
        get tunnels() {
            return [...new Set(this.carts.map(c => c.tunnel))].sort();
        },
        get visible() {
            const q = this.search.trim().toLowerCase();
            return this.carts.filter(c =>
                (!q || c.code.toLowerCase().includes(q)) && (!this.tunnel || c.tunnel === this.tunnel));
        },
        get capacity() {
            return this.carts.filter(c => this.selected.includes(c.id)).reduce((s, c) => s + c.capacity, 0);
        },
        get missing() {
            return this.target - this.capacity;
        },
        get coverage() {
            return this.target > 0 ? Math.min(100, this.capacity / this.target * 100) : (this.selected.length ? 100 : 0);
        },
        isSelected(id) {
            return this.selected.includes(id);
        },
        toggle(id) {
            this.selected = this.isSelected(id) ? this.selected.filter(x => x !== id) : [...this.selected, id];
        },
        // Les plus gros chariots d'abord, puis le dernier est remplace par le plus petit qui suffit
        auto() {
            const pool = [...this.carts].sort((a, b) => b.capacity - a.capacity);
            const pick = [];
            let remaining = this.target;
            for (const cart of pool) {
                if (remaining <= 0) break;
                pick.push(cart);
                remaining -= cart.capacity;
            }
            if (pick.length) {
                const last = pick.pop();
                const needed = this.target - pick.reduce((s, c) => s + c.capacity, 0);
                const best = pool
                    .filter(c => !pick.includes(c) && c.capacity >= needed)
                    .sort((a, b) => a.capacity - b.capacity)[0];
                pick.push(best || last);
            }
            this.selected = pick.map(c => c.id);
        },
    };
}

// Etape 2 : repartition des poissons dans les chariots (simulation identique au bon de chargement)
function distributionBoard(items, carts, initial) {
    const validIds = carts.map(c => c.id);
    return {
        items,
        carts,
        fmt: fmtKg,
        dist: Object.fromEntries(items.map(i => [
            i.id,
            ((initial || {})[i.id] || []).map(Number).filter(id => validIds.includes(id)),
        ])),
        get allocation() {
            const rest = Object.fromEntries(this.carts.map(c => [c.id, c.capacity]));
            const perCart = Object.fromEntries(this.carts.map(c => [c.id, []]));
            const perItem = {};
            for (const item of this.items) {
                let left = item.weight;
                for (const cid of this.dist[item.id]) {
                    if (left <= 0) break;
                    const take = Math.min(left, rest[cid]);
                    if (take <= 0) continue;
                    left -= take;
                    rest[cid] -= take;
                    perCart[cid].push({ fish: item.fish, kg: take });
                }
                perItem[item.id] = left;
            }
            return { rest, perCart, perItem };
        },
        get pairs() {
            return this.items.flatMap(i => this.dist[i.id].map(cid => ({ key: i.id + '-' + cid, item: i.id, cart: cid })));
        },
        get unplaced() {
            return Object.values(this.allocation.perItem).reduce((s, v) => s + v, 0);
        },
        left(itemId) {
            return this.allocation.perItem[itemId] || 0;
        },
        free(cartId) {
            return this.allocation.rest[cartId] || 0;
        },
        has(itemId, cartId) {
            return this.dist[itemId].includes(cartId);
        },
        toggle(itemId, cartId) {
            const list = this.dist[itemId];
            this.dist[itemId] = list.includes(cartId) ? list.filter(x => x !== cartId) : [...list, cartId];
        },
        clear() {
            this.items.forEach(i => { this.dist[i.id] = []; });
        },
        auto() {
            const rest = Object.fromEntries(this.carts.map(c => [c.id, c.capacity]));
            const sortedCarts = [...this.carts].sort((a, b) => b.capacity - a.capacity);
            this.clear();
            [...this.items].sort((a, b) => b.weight - a.weight).forEach(item => {
                let left = item.weight;
                const chosen = [];
                for (const cart of sortedCarts) {
                    if (left <= 0) break;
                    if (rest[cart.id] <= 0) continue;
                    const take = Math.min(left, rest[cart.id]);
                    chosen.push(cart.id);
                    left -= take;
                    rest[cart.id] -= take;
                }
                if (!chosen.length && sortedCarts.length) chosen.push(sortedCarts[0].id);
                this.dist[item.id] = chosen;
            });
        },
    };
}

// Etape 3 : placement des chariots dans les tunnels avec controle de capacite
function tunnelBoard(tunnels, carts) {
    const initial = Object.fromEntries(carts.map(c => [c.cart_id, c.tunnel_id]));
    return {
        tunnels,
        carts,
        fmt: fmtKg,
        placement: { ...initial },
        get dirty() {
            return this.carts.some(c => this.placement[c.cart_id] !== initial[c.cart_id]);
        },
        get unplaced() {
            return this.carts.filter(c => !this.placement[c.cart_id]).length;
        },
        countIn(tunnelId) {
            return Object.values(this.placement).filter(t => t === tunnelId).length;
        },
        used(t) {
            return t.occupied_others + this.countIn(t.id);
        },
        over(t) {
            return t.capacity > 0 && this.used(t) > t.capacity;
        },
        pct(t, n) {
            const total = t.capacity > 0 ? t.capacity : Math.max(1, this.used(t));
            return Math.min(100, n / total * 100);
        },
        canPlace(t, cartId) {
            return this.placement[cartId] === t.id || t.capacity <= 0 || this.used(t) < t.capacity;
        },
        fitsAll(t) {
            return t.capacity <= 0 || t.occupied_others + this.carts.length <= t.capacity;
        },
        place(cartId, tunnelId) {
            const t = this.tunnels.find(x => x.id === tunnelId);
            if (t && this.canPlace(t, cartId)) this.placement[cartId] = tunnelId;
        },
        allIn(t) {
            if (this.fitsAll(t)) this.carts.forEach(c => { this.placement[c.cart_id] = t.id; });
        },
    };
}

// Cartonnage : une carte par poisson, plusieurs types de carton possibles par poisson
function packagingForm(config) {
    let uid = 0;
    const line = (fishId, cartonId = null, cartons = null) => ({ uid: ++uid, fish_type_id: Number(fishId), carton_type_id: cartonId, cartons });

    return {
        orders: config.orders,
        fishTypes: config.fishTypes,
        cartonTypes: config.cartonTypes,
        byFish: config.cartonTypesByFish,
        orderId: config.orderId ? String(config.orderId) : '',
        coldRoomId: config.coldRoomId ? String(config.coldRoomId) : '',
        lines: [],
        addFishId: '',
        fmt: fmtKg,
        init() {
            if (config.lines && config.lines.length) {
                this.lines = config.lines.map(l => line(l.fish_type_id, Number(l.carton_type_id), Number(l.cartons_count)));
                if (!this.coldRoomId && config.lines[0].cold_room_id) this.coldRoomId = String(config.lines[0].cold_room_id);
            } else if (this.orderId) {
                this.suggest();
            }
            this.$watch('orderId', () => this.suggest());
        },
        get order() {
            return this.orders.find(o => String(o.id) === this.orderId) || null;
        },
        // Poids trie par poisson pour l'ordre choisi
        get fishWeights() {
            return this.order ? this.order.fish : {};
        },
        get fishIds() {
            const ids = Object.keys(this.fishWeights).map(Number);
            this.lines.forEach(l => { if (!ids.includes(l.fish_type_id)) ids.push(l.fish_type_id); });
            return ids;
        },
        get availableFish() {
            return this.fishTypes.filter(f => !this.fishIds.includes(f.id));
        },
        fishName(id) {
            return (this.fishTypes.find(f => f.id === id) || {}).name || '?';
        },
        cartonsFor(fishId) {
            const linked = this.byFish[fishId] || [];
            const linkedIds = linked.map(c => c.id);
            return [...linked, ...this.cartonTypes.filter(c => !linkedIds.includes(c.id))];
        },
        cartonWeight(fishId, cartonId) {
            const c = this.cartonsFor(fishId).find(x => x.id === cartonId);
            return c ? c.weight_kg : 0;
        },
        linesFor(fishId) {
            return this.lines.filter(l => l.fish_type_id === fishId);
        },
        packedKg(fishId) {
            return this.linesFor(fishId).reduce((s, l) => s + (Number(l.cartons) || 0) * this.cartonWeight(fishId, l.carton_type_id), 0);
        },
        // Poids encore a cartonner pour ce poisson, sans compter la ligne donnee
        remainingFor(fishId, except = null) {
            const target = this.fishWeights[fishId] || 0;
            const packed = this.linesFor(fishId)
                .filter(l => !except || l.uid !== except.uid)
                .reduce((s, l) => s + (Number(l.cartons) || 0) * this.cartonWeight(fishId, l.carton_type_id), 0);
            return Math.max(0, target - packed);
        },
        defaultCarton(fishId) {
            const linked = this.byFish[fishId] || [];
            return (linked.find(c => c.is_default) || linked[0] || this.cartonTypes[0] || {}).id || null;
        },
        fill(l) {
            const w = this.cartonWeight(l.fish_type_id, l.carton_type_id);
            const rest = this.remainingFor(l.fish_type_id, l);
            l.cartons = w > 0 && rest > 0 ? Math.ceil(rest / w) : (l.cartons || null);
        },
        pickCarton(l, cartonId) {
            l.carton_type_id = cartonId;
            this.fill(l);
        },
        addLine(fishId) {
            const l = line(fishId, this.defaultCarton(fishId));
            this.lines.push(l);
            this.fill(this.lines[this.lines.length - 1]);
        },
        addFish() {
            if (!this.addFishId) return;
            this.addLine(Number(this.addFishId));
            this.addFishId = '';
        },
        removeLine(l) {
            this.lines = this.lines.filter(x => x.uid !== l.uid);
        },
        suggest() {
            this.lines = [];
            Object.keys(this.fishWeights).forEach(fishId => this.addLine(Number(fishId)));
        },
        get totals() {
            const target = Object.values(this.fishWeights).reduce((s, v) => s + v, 0);
            const cartons = this.lines.reduce((s, l) => s + (Number(l.cartons) || 0), 0);
            const packed = this.fishIds.reduce((s, id) => s + this.packedKg(id), 0);
            return { target, cartons, packed, diff: packed - target };
        },
        get valid() {
            return this.orderId && this.lines.length && this.lines.every(l => l.carton_type_id && Number(l.cartons) >= 1);
        },
    };
}

// Temps d'un chariot dans le tunnel, mis a jour en direct (fenetre attendue : minH a maxH heures)
function tunnelTimer(startIso, minH, maxH) {
    const span = h => {
        const total = Math.max(0, Math.floor(h * 60));
        const d = Math.floor(total / 1440), hh = Math.floor((total % 1440) / 60), mm = total % 60;
        return (d ? d + ' j ' : '') + (d || hh ? hh + ' h ' : '') + mm + ' min';
    };
    return {
        start: new Date(startIso).getTime(),
        now: Date.now(),
        minH,
        maxH,
        init() { setInterval(() => { this.now = Date.now(); }, 30000); },
        get hours() { return Math.max(0, (this.now - this.start) / 3600000); },
        get elapsed() { return span(this.hours); },
        get state() { return this.hours < this.minH ? 'freezing' : (this.hours <= this.maxH ? 'ready' : 'late'); },
        get pct() { return Math.min(100, this.hours / this.maxH * 100); },
        get minPct() { return this.minH / this.maxH * 100; },
        get stateLabel() { return { freezing: 'En congélation', ready: 'Prêt à sortir', late: 'À sortir — dépassé' }[this.state]; },
        get hint() {
            if (this.state === 'freezing') return 'prêt dans ' + span(this.minH - this.hours);
            if (this.state === 'ready') return 'sortie avant ' + span(this.maxH - this.hours);
            return 'dépassé de ' + span(this.hours - this.maxH);
        },
        get color() { return { freezing: 'sky', ready: 'emerald', late: 'red' }[this.state]; },
    };
}

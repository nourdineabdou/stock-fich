// Fiche de traitement : reception -> filtrage (non utilisable) -> tri par type de poisson -> frais de traitement
function processingForm(config) {
    let uid = 0;
    const num = v => (v === '' || v === null || v === undefined || isNaN(Number(v))) ? 0 : Number(v);
    const line = (fishId = '', weight = '', price = '') => ({ uid: ++uid, fish_type_id: fishId ? String(fishId) : '', weight, price });

    return {
        fishTypes: config.fishTypes,
        arrivals: config.arrivals,
        clientId: config.clientId ? String(config.clientId) : '',
        arrivalId: config.arrivalId ? String(config.arrivalId) : '',
        input: config.input ?? '',
        waste: config.waste ?? '',
        returned: config.returned ?? '',
        price: config.price ?? '',
        lines: (config.lines || []).map(l => line(l.fish_type_id, l.weight, l.price)),

        init() {
            if (!this.lines.length) this.lines.push(line('', '', this.price));
            if (this.arrivalId && !num(this.input)) this.applyArrival();
            this.$watch('arrivalId', () => this.applyArrival());
        },
        applyArrival() {
            const a = this.arrivals.find(x => String(x.id) === this.arrivalId);
            if (!a) return;
            if (a.client_id) this.clientId = String(a.client_id);
            if (a.net_weight) this.input = a.net_weight;
        },

        fmt(n, d = 2) {
            return Number(n || 0).toLocaleString('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: d });
        },
        get received() { return num(this.input); },
        get usable() { return Math.max(0, this.received - num(this.waste) - num(this.returned)); },
        get sorted() { return this.lines.reduce((s, l) => s + num(l.weight), 0); },
        get remaining() { return this.usable - this.sorted; },
        // Un seul prix par kg pour toute la quantite triee
        get total() { return this.sorted * num(this.price); },
        // Poids envoye au stockage : le trie s'il existe, sinon l'utilisable
        get outputWeight() { return this.sorted > 0 ? this.sorted : this.usable; },
        get overReceived() { return this.received > 0 && num(this.waste) + num(this.returned) + this.sorted > this.received + 0.001; },
        pct(v) { return this.received > 0 ? Math.min(100, num(v) / this.received * 100) : 0; },
        fishName(id) { return (this.fishTypes.find(f => String(f.id) === String(id)) || {}).name || ''; },
        isUsed(id) { return this.lines.some(l => l.fish_type_id === String(id)); },

        addFish(id) {
            const empty = this.lines.find(l => !l.fish_type_id && !num(l.weight));
            if (empty) {
                empty.fish_type_id = String(id);
                if (this.remaining > 0) empty.weight = +this.remaining.toFixed(2);
                return;
            }
            this.lines.push(line(id, this.remaining > 0 ? +this.remaining.toFixed(2) : '', this.price));
        },
        addLine() { this.lines.push(line('', '', this.price)); },
        removeLine(l) {
            this.lines = this.lines.filter(x => x.uid !== l.uid);
            if (!this.lines.length) this.addLine();
        },
        putRemainder(l) {
            if (this.remaining > 0) l.weight = +(num(l.weight) + this.remaining).toFixed(2);
        },
        remainderToWaste() {
            if (this.remaining > 0) this.waste = +(num(this.waste) + this.remaining).toFixed(2);
        },
    };
}

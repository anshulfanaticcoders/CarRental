import { ref } from 'vue';

export function useExtrasState() {
    const selectedExtras = ref({});

    const isRequiredExtra = (extra) => {
        return !!extra.required;
    };

    const getMaxQuantity = (extra) => {
        const max = extra.numberAllowed || extra.maxQuantity || null;
        return max ? Math.max(parseInt(max), 1) : 1;
    };

    const setExtraQuantity = (extra, quantity) => {
        const id = extra.id;
        const max = getMaxQuantity(extra);
        const required = isRequiredExtra(extra);
        const clamped = Math.min(Math.max(quantity, required ? 1 : 0), max);
        if (clamped <= 0) {
            delete selectedExtras.value[id];
            return;
        }
        selectedExtras.value[id] = clamped;
    };

    const updateExtraQuantity = (extra, delta) => {
        const id = extra.id;
        const current = selectedExtras.value[id] || 0;
        setExtraQuantity(extra, current + delta);
    };

    const toggleExtra = (extra) => {
        if (isRequiredExtra(extra)) {
            return;
        }
        const id = extra.id;
        if (selectedExtras.value[id]) {
            delete selectedExtras.value[id];
        } else {
            selectedExtras.value[id] = 1;
        }
    };

    return {
        selectedExtras,
        isRequiredExtra,
        getMaxQuantity,
        setExtraQuantity,
        updateExtraQuantity,
        toggleExtra,
    };
}

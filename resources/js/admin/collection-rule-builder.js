function replacePrefix(node, prefix) {
    node.querySelectorAll('[name]').forEach((input) => {
        input.name = input.name.replace(/__PREFIX__/g, prefix);
    });
}

function clearCardInputs(card) {
    card.querySelectorAll('input, select, textarea').forEach((input) => {
        if (input.type === 'checkbox' || input.type === 'radio') {
            input.checked = false;

            return;
        }

        if (input.tagName === 'SELECT' && input.multiple) {
            Array.from(input.options).forEach((option) => {
                option.selected = false;
            });

            return;
        }

        input.value = '';
    });
}

function updateEmptyState(builder) {
    const cardsList = builder.querySelector('[data-rule-cards]');
    const emptyState = builder.querySelector('[data-rule-empty]');

    if (!cardsList || !emptyState) {
        return;
    }

    const hasCards = cardsList.querySelector('[data-rule-card]') !== null;
    emptyState.hidden = hasCards;
}

function updateAddOptions(builder) {
    const cardsList = builder.querySelector('[data-rule-cards]');
    const addSelect = builder.querySelector('[data-rule-add-select]');

    if (!cardsList || !addSelect) {
        return;
    }

    const activeTypes = new Set(
        Array.from(cardsList.querySelectorAll('[data-rule-card]')).map((card) => card.dataset.ruleType),
    );

    Array.from(addSelect.options).forEach((option) => {
        option.hidden = activeTypes.has(option.value);
        option.disabled = activeTypes.has(option.value);
    });

    const nextOption = Array.from(addSelect.options).find((option) => !option.disabled);

    if (nextOption) {
        addSelect.value = nextOption.value;
    }
}

function addRuleCard(builder, type) {
    const cardsList = builder.querySelector('[data-rule-cards]');
    const template = builder.querySelector(`[data-rule-card-template="${type}"]`);
    const prefix = builder.dataset.namePrefix || 'rules';

    if (!cardsList || !template) {
        return;
    }

    if (cardsList.querySelector(`[data-rule-card][data-rule-type="${type}"]`)) {
        return;
    }

    const wrapper = document.createElement('div');
    wrapper.innerHTML = template.innerHTML.trim();
    const card = wrapper.firstElementChild;

    if (!card) {
        return;
    }

    replacePrefix(card, prefix);
    cardsList.appendChild(card);
    updateEmptyState(builder);
    updateAddOptions(builder);
}

function removeRuleCard(card) {
    const builder = card.closest('[data-rule-builder]');

    clearCardInputs(card);
    card.remove();

    if (builder) {
        updateEmptyState(builder);
        updateAddOptions(builder);
    }
}

function initDragAndDrop(cardsList) {
    let draggedCard = null;

    cardsList.addEventListener('dragstart', (event) => {
        const card = event.target.closest('[data-rule-card]');

        if (!card || !cardsList.contains(card)) {
            return;
        }

        draggedCard = card;
        card.classList.add('is-dragging');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', card.dataset.ruleType || 'rule');
    });

    cardsList.addEventListener('dragover', (event) => {
        if (!draggedCard) {
            return;
        }

        event.preventDefault();

        const target = event.target.closest('[data-rule-card]');

        if (!target || target === draggedCard || !cardsList.contains(target)) {
            return;
        }

        const after = (event.clientY - target.getBoundingClientRect().top) > (target.offsetHeight / 2);
        cardsList.insertBefore(draggedCard, after ? target.nextSibling : target);
    });

    cardsList.addEventListener('dragend', () => {
        draggedCard?.classList.remove('is-dragging');
        draggedCard = null;
    });
}

function initGroupDrag(groupsList) {
    let draggedGroup = null;

    groupsList.addEventListener('dragstart', (event) => {
        const group = event.target.closest('[data-rule-group]');

        if (!group || event.target.closest('[data-rule-card]')) {
            return;
        }

        if (!event.target.closest('[data-rule-group-handle]')) {
            event.preventDefault();

            return;
        }

        draggedGroup = group;
        group.classList.add('is-dragging');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', 'group');
    });

    groupsList.addEventListener('dragover', (event) => {
        if (!draggedGroup) {
            return;
        }

        event.preventDefault();

        const target = event.target.closest('[data-rule-group]');

        if (!target || target === draggedGroup) {
            return;
        }

        const after = (event.clientY - target.getBoundingClientRect().top) > (target.offsetHeight / 2);
        groupsList.insertBefore(draggedGroup, after ? target.nextSibling : target);
    });

    groupsList.addEventListener('dragend', () => {
        draggedGroup?.classList.remove('is-dragging');
        draggedGroup = null;
    });
}

export function initRuleBuilder(builder) {
    if (!builder || builder.dataset.ruleBuilderInit === 'true') {
        return;
    }

    builder.dataset.ruleBuilderInit = 'true';

    const cardsList = builder.querySelector('[data-rule-cards]');
    const addButton = builder.querySelector('[data-rule-add]');
    const addSelect = builder.querySelector('[data-rule-add-select]');

    if (cardsList) {
        initDragAndDrop(cardsList);
    }

    builder.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-rule-remove]');

        if (!removeButton) {
            return;
        }

        const card = removeButton.closest('[data-rule-card]');
        if (card) {
            removeRuleCard(card);
        }
    });

    addButton?.addEventListener('click', () => {
        const type = addSelect?.value;

        if (!type) {
            return;
        }

        addRuleCard(builder, type);
    });

    updateEmptyState(builder);
    updateAddOptions(builder);
}

export function initCollectionRuleBuilders(root = document) {
    root.querySelectorAll('[data-rule-builder]').forEach((builder) => initRuleBuilder(builder));

    const groupsList = root.querySelector('#rule-groups-list');

    if (groupsList) {
        initGroupDrag(groupsList);
    }
}

export function reindexRuleGroups(groupsList) {
    groupsList.querySelectorAll('[data-rule-group]').forEach((group, index) => {
        const label = group.querySelector('[data-rule-group-label]');

        if (label) {
            label.textContent = String(index + 1);
        }

        group.querySelectorAll('[data-rule-builder]').forEach((builder) => {
            const prefix = `rules[groups][${index}]`;
            builder.dataset.namePrefix = prefix;

            builder.querySelectorAll('[name^="rules[groups]"], [name="__PREFIX__"]').forEach((input) => {
                input.name = input.name
                    .replace(/rules\[groups\]\[\d+]/g, prefix)
                    .replace(/__PREFIX__/g, prefix);
            });
        });
    });
}

export function initCollectionGroupRules() {
    const useGroupsToggle = document.getElementById('rules_use_groups');
    const flatRules = document.getElementById('flat-rules');
    const groupRules = document.getElementById('group-rules');
    const groupsList = document.getElementById('rule-groups-list');
    const addButton = document.querySelector('[data-rule-group-add]');
    const groupTemplate = document.getElementById('rule-group-template');
    const builderTemplate = document.getElementById('rule-group-builder-template');
    const maxGroups = 5;

    if (!useGroupsToggle || !flatRules || !groupRules || !groupsList || !addButton || !groupTemplate || !builderTemplate) {
        return;
    }

    const toggleMode = () => {
        const enabled = useGroupsToggle.checked;
        flatRules.classList.toggle('hidden', enabled);
        groupRules.classList.toggle('hidden', !enabled);
    };

    useGroupsToggle.addEventListener('change', toggleMode);

    const mountBuilder = (group, index) => {
        const fieldsContainer = group.querySelector('[data-rule-group-fields]');

        if (!fieldsContainer || fieldsContainer.children.length > 0) {
            return;
        }

        const prefix = `rules[groups][${index}]`;
        const wrapper = document.createElement('div');
        wrapper.innerHTML = builderTemplate.innerHTML.trim().replace(/__PREFIX__/g, prefix);
        const builder = wrapper.firstElementChild;

        if (!builder) {
            return;
        }

        fieldsContainer.appendChild(builder);
        initRuleBuilder(builder);
    };

    addButton.addEventListener('click', () => {
        if (groupsList.querySelectorAll('[data-rule-group]').length >= maxGroups) {
            return;
        }

        const index = groupsList.querySelectorAll('[data-rule-group]').length;
        const clone = groupTemplate.content.firstElementChild.cloneNode(true);
        groupsList.appendChild(clone);
        mountBuilder(clone, index);
        reindexRuleGroups(groupsList);
    });

    groupsList.addEventListener('click', (event) => {
        if (!event.target.matches('[data-rule-group-remove]')) {
            return;
        }

        const groups = groupsList.querySelectorAll('[data-rule-group]');

        if (groups.length <= 1) {
            return;
        }

        event.target.closest('[data-rule-group]')?.remove();
        reindexRuleGroups(groupsList);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initCollectionRuleBuilders();
    initCollectionGroupRules();
});

window.CollectionRuleBuilder = {
    init: initCollectionRuleBuilders,
    initBuilder: initRuleBuilder,
    reindexGroups: reindexRuleGroups,
};

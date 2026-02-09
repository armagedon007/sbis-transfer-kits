const { createApp } = Vue;

const API_BASE = '/api';

createApp({
    data() {
        return {
            warehouses: [],
            fromWarehouse: '',
            toWarehouse: '',
            allKits: [],
            filteredKits: [],
            selectedKits: [],
            searchQuery: '',
            loading: false,
            creating: false,
            error: '',
            successMessage: '',
            editingRowIndex: null,
        };
    },

    computed: {
        canCreateDocument() {
            return this.fromWarehouse &&
                this.toWarehouse &&
                this.fromWarehouse !== this.toWarehouse &&
                this.selectedKits.length > 0;
        },

        totalSum() {
            return this.selectedKits
                .filter(kit => !kit.isEditing)
                .reduce((sum, kit) => {
                    if (!kit.items || kit.items.length === 0) return sum;

                    return sum + (kit.cost * (kit.quantity || 1));
                }, 0)
                .toFixed(2);
        },
    },

    methods: {
        async loadWarehouses() {
            try {
                const response = await fetch(`${API_BASE}/warehouses.php`);
                const data = await response.json();

                if (data.success) {
                    this.warehouses = data.data;

                    const warehouse = this.warehouses.find(w => w.name === 'Склад');
                    const shop = this.warehouses.find(w => w.name.includes('Морской'));

                    if (warehouse) this.fromWarehouse = warehouse.id;
                    if (shop) this.toWarehouse = shop.id;
                } else {
                    this.error = data.error;
                }
            } catch (err) {
                this.error = 'Ошибка загрузки складов: ' + err.message;
            }
        },

        async loadKits() {
            this.loading = true;
            this.error = '';

            try {
                const response = await fetch(`${API_BASE}/kits.php`);
                const data = await response.json();

                if (data.success) {
                    this.allKits = data.data;
                } else {
                    this.error = data.error;
                }
            } catch (err) {
                this.error = 'Ошибка загрузки комплектов: ' + err.message;
            } finally {
                this.loading = false;
            }
        },

        filterKits() {
            const query = this.searchQuery.toLowerCase().trim();

            // Получаем список уже выбранных идентификаторов
            const selectedIdentifiers = this.selectedKits
                .filter(kit => kit.identifier && !kit.isEditing)
                .map(kit => kit.identifier);

            // Фильтруем комплекты
            let availableKits = this.allKits.filter(kit =>
                !selectedIdentifiers.includes(kit.Identifier)
            );

            if (!query) {
                // Если пусто - показываем все доступные комплекты
                this.filteredKits = availableKits.slice(0, 50);
                return;
            }

            // Фильтруем по запросу
            this.filteredKits = availableKits
                .filter(kit =>
                    kit.Name && kit.Name.toLowerCase().includes(query) ||
                    kit.Code && kit.Code.toString().includes(query) ||
                    kit.Identifier && kit.Identifier.toLowerCase().includes(query)
                )
                .slice(0, 50);
        },

        showAllKits(index) {
            // При клике на поле показываем все доступные комплекты
            this.editingRowIndex = index;
            this.searchQuery = '';

            // Получаем список уже выбранных идентификаторов
            const selectedIdentifiers = this.selectedKits
                .filter(kit => kit.identifier && !kit.isEditing)
                .map(kit => kit.identifier);

            // Показываем только не выбранные комплекты
            this.filteredKits = this.allKits
                .filter(kit => !selectedIdentifiers.includes(kit.Identifier))
                .slice(0, 50);
        },

        closeDropdown() {
            // Закрываем список с задержкой для blur
            this.filteredKits = [];
            console.log('ты тут')
        },

        handleBodyClick(event) {
            // Проверяем, что клик не по input и не по dropdown
            if (!event.target.closest('.select-search') && !event.target.closest('.add-row')) {
                this.filteredKits = [];
            }
        },

        addNewRow() {
            const newRow = {
                id: Date.now(),
                name: '',
                code: '',
                identifier: '',
                quantity: 1,
                cost: 0,
                expanded: false,
                items: [],
                isEditing: true,
            };
            this.selectedKits.push(newRow);
            this.editingRowIndex = this.selectedKits.length - 1;
            this.searchQuery = '';

            // Сразу показываем список комплектов
            const selectedIdentifiers = this.selectedKits
                .filter(kit => kit.identifier && !kit.isEditing)
                .map(kit => kit.identifier);

            this.filteredKits = this.allKits
                .filter(kit => !selectedIdentifiers.includes(kit.Identifier))
                .slice(0, 50);
        },

        cancelEdit(index) {
            if (!this.selectedKits[index].identifier) {
                this.selectedKits.splice(index, 1);
            }
            this.editingRowIndex = null;
            this.searchQuery = '';
            this.filteredKits = [];
        },

        async selectKit(kit, index) {
            try {
                // Загружаем состав комплекта при выборе
                const response = await fetch(`${API_BASE}/products.php?kitId=${kit.Id}`);
                const data = await response.json();

                const items = data.success ? data.data : [];
                const totalCost = items.reduce((sum, item) => sum + (item.NomenclatureInfo === 'Product' ? item.SumPlannedCost : item.PriceCost), 0);

                this.selectedKits[index].name = kit.Name;
                this.selectedKits[index].code = kit.Code;
                this.selectedKits[index].identifier = kit.Identifier;
                this.selectedKits[index].expanded = false;
                this.selectedKits[index].items = items;
                this.selectedKits[index].price = kit.Price;
                this.selectedKits[index].cost = totalCost;
                this.selectedKits[index].isEditing = false;

                this.editingRowIndex = null;
                this.searchQuery = '';
                this.filteredKits = [];
            } catch (err) {
                this.error = 'Ошибка загрузки состава комплекта: ' + err.message;
            }
        },

        toggleKit(index) {
            this.selectedKits[index].expanded = !this.selectedKits[index].expanded;
        },

        removeKit(index) {
            this.selectedKits.splice(index, 1);
        },

        getKitSum(kit) {
            return (kit.cost * (kit.quantity || 1)).toFixed(2);
        },

        validateWarehouses() {
            if (this.fromWarehouse && this.toWarehouse &&
                this.fromWarehouse === this.toWarehouse) {
                this.error = 'Склад отправитель и получатель не могут совпадать';
            } else {
                this.error = '';
            }
        },

        async createDocument() {
            if (!this.canCreateDocument) return;

            this.creating = true;
            this.error = '';
            this.successMessage = '';

            try {
                const response = await fetch(`${API_BASE}/documents.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        fromWarehouse: this.warehouses.find(w => w.id === this.fromWarehouse),
                        toWarehouse: this.warehouses.find(w => w.id === this.toWarehouse),
                        kits: this.selectedKits,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    this.successMessage = '✓ Документ успешно создан!';
                    this.selectedKits = [];

                    setTimeout(() => {
                        this.successMessage = '';
                    }, 5000);
                } else {
                    this.error = 'Ошибка создания документа: ' + data.error;
                }
            } catch (err) {
                this.error = 'Ошибка отправки: ' + err.message;
            } finally {
                this.creating = false;
            }
        },
    },

    mounted() {
        this.loadWarehouses();
        this.loadKits();

        // Добавляем обработчик клика по body
        document.body.addEventListener('click', this.handleBodyClick);
    },

    beforeUnmount() {
        // Удаляем обработчик
        document.body.removeEventListener('click', this.handleBodyClick);
    },
}).mount('#app');

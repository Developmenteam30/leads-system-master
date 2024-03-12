<template>
    <component
        :is="tag"
        :key="datatableKey"
        :class="className"
        :style="{ maxWidth: width }"
    >
        <div
            class="datatable-inner table-responsive"
            style="overflow: auto; position: relative"
            ref="datatable"
        >
            <MDBScrollbar
                :width="width"
                :height="height"
                :wheelPropagation="true"
                style="background-color: inherit"
            >
                <table class="table datatable-table">
                    <thead v-if="data.columns" class="datatable-header">
                    <tr v-if="data.subColumns && data.subColumns.length">
                        <th
                            v-for="(col, colKey) in data.subColumns"
                            :key="'subcol-' + colKey"
                            scope="subcol"
                            :style="{
                                cursor: col.sort !== false ? 'pointer' : 'default',
                                minWidth: col.width + 'px',
                                maxWidth: col.width + 'px',
                                left: col.fixed && !col.right ? col.left + 'px' : '',
                                right: col.fixed && col.right ? 0 : '',
                            }"
                            :class="(fixedHeader || col.fixed) && 'fixed-cell'"
                            @click="col.sort !== false && sortAndFilter(col.field)"
                            :colSpan="col?.colSpan ?? 1"
                        >
                            <i
                                v-if="col.sort !== false"
                                class="datatable-sort-icon fas fa-arrow-up"
                                :class="orderBy && orderKey === col.field && 'active'"
                                :style="{
                                    transform:
                                    orderBy === 'desc' && orderKey === col.field
                                        ? 'rotate(180deg)'
                                        : 'rotate(0deg)',
                                }"
                            ></i>
                            {{ col.label }}
                        </th>
                    </tr>
                    <tr>
                        <th
                            v-for="(col, colKey) in data.columns"
                            :key="'col-' + colKey"
                            scope="col"
                            :style="{
                                cursor: col.sort !== false ? 'pointer' : 'default',
                                minWidth: col.width + 'px',
                                maxWidth: col.width + 'px',
                                left: col.fixed && !col.right ? col.left + 'px' : '',
                                right: col.fixed && col.right ? 0 : '',
                            }"
                            :class="(fixedHeader || col.fixed) && 'fixed-cell'"
                            @click="col.sort !== false && sortAndFilter(col.field)"
                            :colSpan="col?.colSpan ?? 1"
                        >
                            <i
                                v-if="col.sort !== false"
                                class="datatable-sort-icon fas fa-arrow-up"
                                :class="orderBy && orderKey === col.field && 'active'"
                                :style="{
                                    transform:
                                    orderBy === 'desc' && orderKey === col.field
                                        ? 'rotate(180deg)'
                                        : 'rotate(0deg)',
                                }"
                            ></i>
                            {{ col.label }}
                        </th>
                    </tr>
                    </thead>
                    <tbody
                        v-if="(data.rows && data.rows.length > 0) || loading"
                        class="datatable-body"
                    >
                    <tr
                        v-for="(row, rowKey) in data.rows.slice(
                            pageKey * rowsPerPage,
                            pageKey * rowsPerPage + rowsPerPage
                        )"
                        :key="'row-' + row.mdbIndex"
                        :data-mdb-index="row.mdbIndex"
                        :class="row.selected && 'active'"
                        scope="row"
                    >
                        <td
                            v-for="(col, colKey) in data.columns"
                            :key="'cell-' + colKey"
                            :style="[
                                row.formats && row.formats[col.field],
                                {
                                    minWidth: col.width + 'px',
                                    maxWidth: col.width + 'px',
                                    left: col.fixed && !col.right ? col.left + 'px' : false,
                                    right: col.fixed && col.right ? 0 : false,
                                },
                            ]"
                            :class="col.fixed && 'fixed-cell'"
                            :contenteditable="edit ? true : null"
                            @blur="handleCellBlur($event, rowKey, col.field)"
                            @click="handleCellClick(row.mdbIndex, colKey)"
                        >
                            <MDBCheckbox
                                v-if="selectable && colKey === 0"
                                v-model="row.selected"
                                :data-mdb-row-index="row.mdbIndex"
                                @click.stop
                                @change="handleCheckboxChange(row.mdbIndex, row.selected)"
                            />
                            <span v-html="
                                row[col.field]
                                ? row[col.field]
                                : row[col.field] === 0
                                ? 0
                                : defaultValue
                            "></span>
                        </td>
                    </tr>
                    </tbody>
                    <tbody v-else class="datatable-body">
                    <tr>
                        <td>
                            {{ noFoundMessage }}
                        </td>
                    </tr>
                    </tbody>
                    <tfoot
                        v-if="(data.totals && data.totals.length > 0) || loading">
                    <tr
                        v-for="(row, rowKey) in data.totals"
                        :key="'row-' + row.mdbIndex"
                        :data-mdb-index="row.mdbIndex"
                        :class="row.selected && 'active'"
                        scope="row"
                        @click="handleRowClick(row.mdbIndex)"
                    >
                        <th
                            v-for="(col, colKey) in data.columns"
                            :key="'cell-' + colKey"
                            :style="[
                                row.formats && row.formats[col.field],
                                {
                                    minWidth: col.width + 'px',
                                    maxWidth: col.width + 'px',
                                    left: col.fixed && !col.right ? col.left + 'px' : false,
                                    right: col.fixed && col.right ? 0 : false,
                                },
                            ]"
                            :class="col.fixed && 'fixed-cell'"
                            :contenteditable="edit ? true : null"
                            @blur="handleCellBlur($event, rowKey, col.field)"
                            v-html="
                            row[col.field]
                                ? row[col.field]
                                : row[col.field] === 0
                                ? 0
                                : defaultValue
                            "
                        ></th>
                    </tr>
                    </tfoot>
                </table>
                <p v-if="showCount && data.rows"><strong>COUNT: {{ data.rows.length }}</strong></p>
            </MDBScrollbar>
        </div>

        <div v-if="loading" class="datatable-loader bg-light">
      <span class="datatable-loader-inner"
      ><span class="datatable-progress" :class="loaderClass"></span
      ></span>
        </div>
        <p v-if="loading" class="text-center text-muted my-4">
            {{ loadingMessage }}
        </p>

        <div v-if="pagination" class="datatable-pagination">
            <div class="datatable-select-wrapper">
                <p class="datatable-select-text">{{ rowsText }}</p>
                <MDBSelect
                    v-model:options="selectOptions"
                    v-model:selected="rowsPerPage"
                />
            </div>
            <div class="datatable-pagination-nav">
                {{ hasRows ? `${firstRowIndex} - ${lastRowIndex}` : 0 }}
                {{ ofPaginationText }}
                {{ data.rows ? data.rows.length : "" }}
            </div>
            <div class="datatable-pagination-buttons">
                <MDBBtn
                    v-if="fullPagination"
                    :ripple="false"
                    color="link"
                    class="datatable-pagination-button datatable-pagination-start"
                    :disabled="pageKey === 0 ? true : null"
                    @click="
            () => {
              pageKey = 0;
              $nextTick(() => $emit('render', data));
            }
          "
                >
                    <i class="fa fa-angle-double-left"></i>
                </MDBBtn>
                <MDBBtn
                    :ripple="false"
                    color="link"
                    class="datatable-pagination-button datatable-pagination-left"
                    :disabled="pageKey === 0 ? true : null"
                    @click="
            () => {
              pageKey--;
              $nextTick(() => $emit('render', data));
            }
          "
                >
                    <i class="fa fa-chevron-left"></i>
                </MDBBtn>
                <MDBBtn
                    :ripple="false"
                    color="link"
                    class="datatable-pagination-button datatable-pagination-right"
                    :disabled="pageKey === pages - 1 || pages === 0 ? true : null"
                    @click="
            () => {
              pageKey++;
              $nextTick(() => $emit('render', data));
            }
          "
                >
                    <i class="fa fa-chevron-right"></i>
                </MDBBtn>
                <MDBBtn
                    v-if="fullPagination"
                    :ripple="false"
                    color="link"
                    class="datatable-pagination-button datatable-pagination-start"
                    :disabled="pageKey === pages - 1 || pages === 0 ? true : null"
                    @click="
            () => {
              pageKey = pages - 1;
              $nextTick(() => $emit('render', data));
            }
          "
                >
                    <i class="fa fa-angle-double-right"></i>
                </MDBBtn>
            </div>
        </div>
    </component>
</template>

<script lang="ts">
export default {
    name: "CustomDatatable",
};
</script>

<script setup lang="ts">
import {
    computed,
    ref,
    onMounted,
    onUnmounted,
    watch,
    nextTick,
    useSlots,
    VNode,
    PropType,
} from "vue";
import {MDBSelect, MDBCheckbox, MDBBtn, MDBScrollbar} from "mdb-vue-ui-kit";
import {DateTime, Duration} from "luxon";

interface DatatableItem {
    [props: string]: any;
}

interface Data {
    rows?: DatatableItem[];
    columns?: DatatableItem[];
    subColumns?: DatatableItem[];
    totals?: DatatableItem[];
}

const props = defineProps({
    bordered: Boolean,
    borderless: Boolean,
    borderColor: String,
    clickableRows: Boolean,
    color: String,
    dark: Boolean,
    defaultValue: {
        type: String,
        default: "-",
    },
    dataset: {
        type: Object as PropType<{
            columns?: string[] | { label: string; field: string; sort?: boolean, colSpan: number }[];
            subColumns?: string[] | { label: string; field: string; sort?: boolean }[];
            rows?: string[] | { [props: string]: string | number }[];
            totals?: string[] | { [props: string]: string | number }[];
        }>,
        default() {
            return {
                columns: [],
                subColumns: [],
                rows: [],
                totals: [],
            };
        },
    },
    edit: Boolean,
    entries: {
        type: Number,
        default: 10,
    },
    entriesOptions: {
        type: Array as PropType<(string | number)[]>,
        default: () => ["All"],
    },
    fixedHeader: Boolean,
    fullPagination: Boolean,
    hover: Boolean,
    loaderClass: {
        type: String,
        default: "bg-primary",
    },
    loading: Boolean,
    loadingMessage: {
        type: String,
        default: "Loading results...",
    },
    autoHeight: Boolean,
    maxHeight: [Number, String],
    maxWidth: {
        type: [Number, String],
        default: "100%",
    },
    multi: Boolean,
    noFoundMessage: {
        type: String,
        default: "No matching results found",
    },
    ofPaginationText: {
        type: String,
        default: "of",
    },
    pagination: {
        type: Boolean,
        default: true,
    },
    rowsText: {
        type: String,
        default: "Rows per page:",
    },
    search: String,
    searchColumns: {
        type: Array as PropType<string[]>,
        default: () => [],
    },
    selectable: Boolean,
    sm: Boolean,
    sortField: String,
    sortOrder: String,
    striped: Boolean,
    tag: {
        type: String,
        default: "div",
    },
    searchCaseSensitive: Boolean,
    disableSortToDefault: Boolean,
    showCount: {
        type: Boolean,
        default: false,
    }
});

const emit = defineEmits([
    "render",
    "selected-rows",
    "selected-indexes",
    "all-selected",
    "all-filtered-rows-selected",
    "cell-click",
    "cell-click-values",
    "row-click",
    "row-click-values",
    "update",
]);

const slots = useSlots();

// Defaults
const className = computed(() => [
    "datatable",
    props.color,
    props.bordered && "datatable-bordered",
    props.borderColor && `border-${props.borderColor}`,
    props.borderless && "datatable-borderless",
    props.clickableRows && "datatable-clickable-rows",
    props.selectable && "datatable-selectable-rows",
    props.dark && "datatable-dark",
    props.hover && "datatable-hover",
    props.loading && "datatable-loading",
    props.sm && "datatable-sm",
    props.striped && "datatable-striped",
]);
const height = computed(() =>
    props.autoHeight ? (clientHeight.value - (datatable.value !== null ? datatable.value.getBoundingClientRect().top : 0) - (props.pagination ? 60 : 20)) + "px" :
        (typeof props.maxHeight === "number" ? props.maxHeight + "px" : props.maxHeight)
);

// Auto height customizations
const clientHeight = ref(document.documentElement.clientHeight);
const getDimensions = () => {
    clientHeight.value = document.documentElement.clientHeight
};
onMounted(() => {
    window.addEventListener('resize', getDimensions);
});
onUnmounted(() => {
    window.removeEventListener('resize', getDimensions);
});


onMounted(() => {
    if (slots.default && slots.default()[0].type === "table") {
        getDataFromSlot(slots.default()[0]);
    } else {
        getDataFromProps();
    }
});

const width = computed(() =>
    typeof props.maxWidth === "number" ? props.maxWidth + "px" : props.maxWidth
);
const data = ref<Data>({});
const rowsPerPage = ref(props.entries);
const datatable = ref(null);
const pageKey = ref(0);
const pages = computed(() =>
    data.value.rows ? Math.ceil(data.value.rows.length / rowsPerPage.value) : 1
);
const firstRowIndex = computed(() =>
    data.value.rows ? pageKey.value * rowsPerPage.value + 1 : 1
);
const lastRowIndex = computed(() =>
    data.value.rows
        ? pageKey.value === pages.value - 1
            ? data.value.rows.length
            : pageKey.value * rowsPerPage.value + rowsPerPage.value
        : rowsPerPage.value
);
const hasRows = computed(() =>
    data.value.rows ? data.value.rows.length > 0 : false
);
const allRows = computed(() => data.value.rows && data.value.rows.length);

const selectOptions = ref(
    props.entriesOptions.map((entry) => {
        return {
            text: entry,
            value: typeof entry === "string" ? allRows : entry,
            selected: entry === rowsPerPage.value,
        };
    })
);
const datatableKey = ref(0);
const defaultData = ref<Data>({});
const columnColSpan: number = ref(1);

// Getting data
onMounted(() => {
    if (slots.default && slots.default()[0].type === "table") {
        getDataFromSlot(slots.default()[0]);
    } else {
        getDataFromProps();
    }
});
if (slots.default) {
    watch(
        () => slots.default(),
        () => {
            if (slots.default().length > 0 && slots.default()[0].type === "table") {
                getDataFromSlot(slots.default()[0]);
            }
        }
    );
} else {
    watch(
        () => props.dataset,
        () => {
            getDataFromProps();
            sort();
            filter();
        },
        {deep: true}
    );
}
watch(
    () => rowsPerPage.value,
    () => {
        pageKey.value = 0;
        datatableKey.value++;
        nextTick(() => emit("render", data.value));
    }
);

// Setting data
const setData = (columns: DatatableItem[], subColumns: DatatableItem[], rows: DatatableItem[], totals: DatatableItem[]) => {
    data.value.columns = columns;
    data.value.subColumns = subColumns;
    data.value.rows = rows;
    data.value.totals = totals;

    setDefaultData(columns, subColumns, rows, totals);
    nextTick(() => emit("render", data.value));
};

const setDefaultData = (columns: DatatableItem[], subColumns: DatatableItem[], rows: DatatableItem[], totals: DatatableItem[]) => {
    defaultData.value.columns = [...columns];
    defaultData.value.subColumns = [...subColumns];
    defaultData.value.rows = [...rows];
    defaultData.value.totals = [...totals];
};

const getGeneratedSubColumns = () => {
    if (props.dataset.subColumns[0].field) {
        return [...props.dataset.subColumns];
    } else {
        return props.dataset.subColumns.map((th: DatatableItem) => {
            return {
                label: th,
                field: th.toLowerCase(),
            };
        });
    }
};
const getGeneratedColumns = () => {
    if (props.dataset.columns[0].field) {
        return [...props.dataset.columns];
    } else {
        return props.dataset.columns.map((th: DatatableItem) => {
            return {
                label: th,
                field: th.toLowerCase(),
            };
        });
    }
};
const getGeneratedRows = (columns: DatatableItem[]) => {
    let rows = [];
    const firstCell = props.dataset.rows[0][columns[0].field];
    if (firstCell || firstCell === 0) {
        rows = props.dataset.rows.map((row: DatatableItem, key: number) => ({
            ...row,
            mdbIndex: key,
            selected: false,
        }));
    } else {
        const rowsArr = props.dataset.rows.map((tr: DatatableItem) => tr);
        rowsArr.forEach((row: DatatableItem, key: number) => {
            rows.push({});
            row.forEach((td: DatatableItem, tdKey: number) => {
                rows[key][columns[tdKey].field] = td;
                rows[key].mdbIndex = key;
                rows[key].selected = false;
            });
        });
    }

    return rows;
};
const getGeneratedTotals = (columns: DatatableItem[]) => {
    let totals = [];
    if (props.dataset.totals[0][columns[0].field]) {
        totals = props.dataset.totals.map((row: DatatableItem, key: number) => ({
            ...row,
            mdbIndex: key,
            selected: false,
        }));
    } else {
        const totalsArr = props.dataset.totals.map((th: DatatableItem) => th);
        totalsArr.forEach((row: DatatableItem, key: number) => {
            totals.push({});
            row.forEach((td: DatatableItem, tdKey: number) => {
                totals[key][columns[tdKey].field] = th;
                totals[key].mdbIndex = key;
                totals[key].selected = false;
            });
        });
    }

    return totals;
};
const setColMarginsAndFormats = (
    columns: DatatableItem[],
    formattedColumns: DatatableItem[]
) => {
    let colMarginLeft = -columns[0].width || 0;
    const colLeftMargins = columns.map((col) => {
        colMarginLeft += col.fixed ? col.width || 0 : 0;
        return colMarginLeft;
    });
    columns.forEach((col, key) => {
        if (col.fixed && col.fixed === "right") {
            col.right = true;
        }

        if ("format" in col) {
            formattedColumns.push({
                field: col.field,
                rules: col.format.value ? col.format.value : col.format,
            });
        }

        col.left = colLeftMargins[key];
    });
};
const formatCells = (
    rows: DatatableItem[],
    totals: DatatableItem[],
    formattedColumns: DatatableItem[]
) => {
    rows.forEach((row, key) => {
        row.formats = {};
        formattedColumns.forEach((col) => {
            row.formats[col.field] = col.rules[key];
        });
    });
};

const createStringWithProperties = (el: VNode): string => {
    let props = "";
    if (el.props) {
        Object.keys(el.props).forEach((key) => {
            props += `${key}="${el.props[key]}"`;
        });
    }
    return props;
};

const createHTMLElementStringFromSlots = (data: VNode[]): string => {
    const content = [];
    data.forEach((el) => {
        if (el.children !== " ") {
            const props = createStringWithProperties(el);
            if (typeof el.type !== "symbol") {
                content.push(`<${el.type} ${props}>`);
            }
        }
        if (el.children) {
            if (typeof el.children === "string") {
                content.push(`${el.children}`);
            } else {
                const childrenElements = createHTMLElementStringFromSlots(
                    el.children as VNode[]
                );
                content.push(childrenElements);
            }
        }

        if (el.children !== " ") {
            if (typeof el.type !== "symbol") {
                content.push(`</${el.type}>`);
            }
        }
    });

    return content.join("");
};

const getDataFromSlot = (slot: VNode) => {
    const columns: DatatableItem[] = slot.children[0].children[0].children.map(
        (th: VNode) => {
            return {
                label: th.children,
                field: (th.children as string).toLowerCase(),
                sort: th.props && th.props["data-mdb-sort"] === "false" ? false : true,
            };
        }
    );
    const subColumns: DatatableItem[] = slot.children[0].children[0].children.map(
        (th: VNode) => {
            return {
                label: th.children,
                field: (th.children as string).toLowerCase(),
                sort: th.props && th.props["data-mdb-sort"] === "false" ? false : true,
            };
        }
    );
    const rows: DatatableItem[] = [];
    const rowsObj: DatatableItem[] = slot.children[1].children.map(
        (tr: VNode) => tr.children
    );
    rowsObj.forEach((row: DatatableItem, key) => {
        rows.push({});
        row.forEach((td: DatatableItem, tdKey: number) => {
            if (typeof td.children === "object") {
                rows[key][columns[tdKey].field] = createHTMLElementStringFromSlots(
                    td.children
                );
            } else {
                rows[key][columns[tdKey].field] = td.children;
            }
            rows[key].mdbIndex = key;
            rows[key].selected = false;
        });
    });
    const totals: DatatableItem[] = [];
    const totalsObj: DatatableItem[] = slot.children[1].children.map(
        (tr: VNode) => tr.children
    );
    totalsObj.forEach((total: DatatableItem, key) => {
        totals.push({});
        total.forEach((td: DatatableItem, tdKey: number) => {
            if (typeof td.children === "object") {
                rows[key][columns[tdKey].field] = createHTMLElementStringFromSlots(
                    td.children
                );
            } else {
                totals[key][columns[tdKey].field] = td.children;
            }
            totals[key].mdbIndex = key;
            totals[key].selected = false;
        });
    });


    setData(columns, subColumns, rows, totals);
};
const getDataFromProps = () => {
    let columns = [];
    let subColumns = [];
    let rows = [];
    let totals = [];

    if (props.dataset.columns && props.dataset.columns.length > 0) {
        columns = getGeneratedColumns();
    }
    if (props.dataset.subColumns && props.dataset.subColumns.length > 0) {
        subColumns = getGeneratedSubColumns();
    }
    if (props.dataset.rows && props.dataset.rows.length > 0) {
        rows = getGeneratedRows(columns);
    }
    if (props.dataset.totals && props.dataset.totals.length > 0) {
        totals = getGeneratedTotals(columns);
    }

    // Formatting
    const formattedColumns = [];
    setColMarginsAndFormats(columns, formattedColumns);
    if (formattedColumns.length > 0) {
        formatCells(rows, totals, formattedColumns);
    }

    setData(columns, subColumns, rows, totals);
};

const setActivePage = (value: number) => {
    if (value < pages.value) {
        pageKey.value = value;
        emit("render", data.value);
    }
};

// Sort
const orderBy = ref(props.sortOrder || null);
const orderKey = ref(props.sortField || null);
const setOrderData = (order: string, key: string) => {
    orderBy.value = order;
    orderKey.value = key;
};

const sanitizeValueForSorting = (value) => {
    if (value === undefined || value === null || value === '') {
        return String('');
    }

    value = String(value);

    // Check if it's a integer, float, or currency value
    if (value.match(/^[0-9,$.]+$/)) {
        return parseFloat(value.replace(/[$,]/g, ''));
    }

    // Check if it's a duration
    if (value.match(/^(\d+):(\d+):(\d+)$/)) {
        const [, hours, minutes, seconds] = value.match(/^(\d+):(\d+):(\d+)$/);
        return Duration.fromObject({hours, minutes, seconds}).as('seconds');
    } else if (value.match(/^(\d+):(\d+)$/)) {
        const [, minutes, seconds] = value.match(/^(\d+):(\d+)$/);
        return Duration.fromObject({minutes, seconds}).as('seconds');
    }

    // Check if it's a time
    if (value.match(/^(\d+):(\d+):(\d+) ?[apAP]m$/)) {
        return DateTime.fromFormat(value, "tt").toFormat('HHmmss');
    } else if (value.match(/^(\d+):(\d+) ?[apAP]m$/)) {
        return DateTime.fromFormat(value, "t").toFormat('HHmmss');
    }

    return value;
}

const sortAsc = () => {
    data.value.rows.sort((a, b) => {
        let valueA = sanitizeValueForSorting(a[orderKey.value]);
        let valueB = sanitizeValueForSorting(b[orderKey.value]);

        return (typeof valueA === 'string') && (typeof valueB === 'string') ? valueA.localeCompare(valueB) : valueA - valueB;
    });
};
const sortDesc = () => {
    data.value.rows.sort((a, b) => {
        let valueA = sanitizeValueForSorting(a[orderKey.value]);
        let valueB = sanitizeValueForSorting(b[orderKey.value]);

        return (typeof valueA === 'string') && (typeof valueB === 'string') ? valueB.localeCompare(valueA) : valueB - valueA;
    });
};
const sortAndFilter = (key: string) => {
    if (
        orderBy.value === null ||
        orderKey.value !== key ||
        (props.disableSortToDefault && orderBy.value === "desc")
    ) {
        setOrderData("asc", key);
        sortAsc();
    } else if (orderBy.value === "asc" && orderKey.value === key) {
        setOrderData("desc", key);
        sortDesc();
    } else {
        setOrderData(null, null);
        data.value.rows = [...defaultData.value.rows];

        if (search.value) {
            filter();
        }
    }

    nextTick(() => emit("render", data.value));
};
const sort = () => {
    if (orderBy.value === "asc") {
        sortAsc();
    } else if (orderBy.value === "desc") {
        sortDesc();
    }
    nextTick(() => emit("render", data.value));
};
onMounted(() => {
    if (orderKey.value) {
        sort();
    }
});

// Search
const search = ref("");
const searchColumns = ref(props.searchColumns as string[]);
const filter = () => {
    if (searchColumns.value.length > 0) {
        data.value.rows = defaultData.value.rows.filter((row) =>
            searchColumns.value
                .map((column) => {
                    const td = row[column]?.toString().replace(/(<([^>]+)>)/gi, "");

                    return props.searchCaseSensitive
                        ? td?.toString().includes(search.value)
                        : td?.toString().toLowerCase().includes(search.value);
                })
                .some((value) => value === true)
        );
    } else {
        data.value.rows = defaultData.value.rows.filter((row) =>
            data.value.columns
                .map((column) => {
                    const td = row[column.field]?.toString().replace(/(<([^>]+)>)/gi, "");

                    return props.searchCaseSensitive
                        ? td?.toString().includes(search.value)
                        : td?.toString().toLowerCase().includes(search.value);
                })
                .some((value) => value === true)
        );
    }

    if (props.selectable && props.multi) {
        updateAllSelectedCheckbox();
    }
};
watch(
    () => props.search,
    (searchString) => {
        search.value = !props.searchCaseSensitive
            ? searchString.toLowerCase()
            : searchString;

        if (searchString === "") {
            data.value.rows = defaultData.value.rows;
        } else {
            filter();
        }
        sort();
        pageKey.value = 0;
    }
);
watch(
    () => props.searchColumns,
    (searchCols: string[]) => {
        searchColumns.value = searchCols;
        filter();
        sort();
        pageKey.value = 0;
    }
);

// Select
const selectedRows = computed(() =>
    defaultData.value.rows.filter((row) => row.selected === true)
);
const selectedIndexes = computed(() =>
    selectedRows.value.map((row) => row.mdbIndex)
);
const allRowsSelected = computed(
    () => selectedIndexes.value.length === defaultData.value.rows.length
);
const allSelectedCheckbox = ref(false);
const allFilteredRowsSelected = computed(() => {
    if (data.value.rows.length === 0) {
        return false;
    }

    let allFilteredRowsSelected = allFilteredRowsSelectedCheckbox.value;

    if (selectedIndexes.value.length >= data.value.rows.length) {
        allFilteredRowsSelected = true;
    }

    data.value.rows.forEach((row) => {
        if (
            row.selected === false &&
            !selectedIndexes.value.includes(row.mdbIndex)
        ) {
            allFilteredRowsSelected = false;
        }
    });

    return allFilteredRowsSelected;
});
const allFilteredRowsSelectedCheckbox = ref(false);
const handleCheckboxChange = (rowId: number, rowChecked: boolean) => {
    if (!props.multi && rowChecked === false) {
        defaultData.value.rows.forEach((row) => {
            if (row.mdbIndex !== rowId) {
                row.selected = false;
            }
        });
        data.value.rows.forEach((row) => {
            if (row.mdbIndex !== rowId) {
                row.selected = false;
            }
        });
    }

    emitSelectedValues();
    updateAllSelectedCheckbox();
};
const toggleSelectAll = () => {
    if (allFilteredRowsSelected.value) {
        data.value.rows.forEach((row) => {
            row.selected = false;
            defaultData.value.rows[row.mdbIndex].selected = false;
        });
    } else {
        data.value.rows.forEach((row) => (row.selected = true));
    }

    emitSelectedValues();
};
const updateAllSelectedCheckbox = () => {
    nextTick(
        () =>
            (allFilteredRowsSelectedCheckbox.value = allFilteredRowsSelected.value)
    );
};
const emitSelectedValues = () => {
    nextTick(() => {
        emit("selected-rows", selectedRows.value);
        emit("selected-indexes", selectedIndexes.value);
        emit("all-selected", allRowsSelected.value);
        emit("all-filtered-rows-selected", allFilteredRowsSelected.value);
    });
};

// Events
const handleRowClick = (index: number) => {
    emit("row-click", index);
    emit("row-click-values", defaultData.value.rows?.[index]);
};
const handleCellClick = (rowIndex: number, colIndex: number) => {
    emit("row-click", rowIndex);
    emit("row-click-values", defaultData.value.rows?.[rowIndex]);
    emit("cell-click", rowIndex, colIndex);
    emit("cell-click-values", defaultData.value.rows?.[rowIndex], colIndex);
};
const handleCellBlur = (event: Event, rowIndex: number, field: string) => {
    if (props.edit) {
        const target = event.target as HTMLElement;
        data.value.rows[rowIndex][field] = target.innerHTML;
        nextTick(() => {
            sort();
            emit("update", data.value);
        });
    }
};

defineExpose({setActivePage});
</script>
<style lang="scss">
.datatable {
    tfoot {
        position: sticky;
        inset-block-end: 0; /* "bottom" */
        background-color: inherit;
        z-index: 3;

        tr {
            background-color: inherit;
            border-bottom: var(--mdb-datatable-thead-tr-border-width) solid var(--datatable-border-color);
        }

        th {
            position: relative;
            border-bottom: none;
            font-weight: var(--mdb-datatable-thead-th-font-weight);
            padding-bottom: 15px !important;

            &:hover {
                .datatable-sort-icon {
                    opacity: 1;
                }
            }
        }
    }
}
</style>

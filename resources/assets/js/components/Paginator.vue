<template>
    <ul class="pagination" v-if="shouldPaginate">
        <li class="page-item" v-show="prevPageUrl" @click.prevent="changePage(currentPage-1)">
            <a class="page-link" href="#" aria-label="Previous" rel="prev">
                <span aria-hidden="true">&laquo;</span>
                <span class="sr-only">Previous</span>
            </a>
        </li>
        <template inline-template v-for="index in dataSet.last_page">
            <li :key="index" class="page-item" :class="currentPage==index ? 'active':''">
                <a class="page-link" href="#" @click.prevent="changePage(index)">{{ index }}</a>
            </li>
        </template>
        <li class="page-item" v-show="nextPageUrl" @click.prevent="changePage(currentPage+1)">
            <a class="page-link" href="#" aria-label="Next" rel="next">
                <span aria-hidden="true">&raquo;</span>
                <span class="sr-only">Next</span>
            </a>
        </li>
    </ul>
</template>

<script>
    export default {
        props: ['dataSet'],

        data() {
            return {
                currentPage: 1,
                prevPageUrl: false,
                nextPageUrl: false
            }
        },

        watch: {
            dataSet() {
                this.currentPage = this.dataSet.current_page;
                this.prevPageUrl = this.dataSet.prev_page_url;
                this.nextPageUrl = this.dataSet.next_page_url;
            },
        },

        computed: {
            shouldPaginate() {
                return !!(this.prevPageUrl || this.nextPageUrl);
            }
        },

        methods: {
            changePage(newPage) {
                this.currentPage = newPage;
                this.$emit('pageChanged', this.currentPage);
                this.updateUrl();
            },

            updateUrl() {
                history.pushState(null, null, '?page=' + this.currentPage);
            }
        }
    }
</script>)
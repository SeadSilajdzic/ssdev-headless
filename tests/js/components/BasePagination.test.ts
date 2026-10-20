import { mount } from '@vue/test-utils';
import BasePagination from '@headless/components/ui/BasePagination.vue';

it('marks the current page and disables previous on the first page', () => {
    const wrapper = mount(BasePagination, {
        props: { currentPage: 1, lastPage: 3, from: 1, to: 10, total: 25, url: (page: number) => `/users?page=${page}` },
    });
    expect(wrapper.get('[aria-current="page"]').text()).toBe('1');
    expect(wrapper.find('a[href="/users?page=0"]').exists()).toBe(false);
    expect(wrapper.find('a[href="/users?page=2"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('Showing 1 to 10 of 25 results');
});

it('collapses long page ranges', () => {
    const wrapper = mount(BasePagination, {
        props: { currentPage: 10, lastPage: 20, from: 91, to: 100, total: 200, url: (page: number) => `?page=${page}` },
    });
    expect(wrapper.findAll('[data-page]').map((link) => link.text())).toEqual(['1', '9', '10', '11', '20']);
});

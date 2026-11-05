import { mount } from '@vue/test-utils';
import RecoveryCodes from '@headless/components/two-factor/RecoveryCodes.vue';

const codes = ['aaaa-bbbb', 'cccc-dddd'];

afterEach(() => vi.restoreAllMocks());

it('copies the codes, one per line', async () => {
    const writeText = vi.fn().mockResolvedValue(undefined);
    Object.defineProperty(navigator, 'clipboard', { value: { writeText }, configurable: true });

    const wrapper = mount(RecoveryCodes, { props: { codes } });
    await wrapper.find('[data-test="copy-codes"]').trigger('click');

    expect(writeText).toHaveBeenCalledWith('aaaa-bbbb\ncccc-dddd');
});

it('does not throw when the clipboard is unavailable', async () => {
    Object.defineProperty(navigator, 'clipboard', { value: undefined, configurable: true });

    const wrapper = mount(RecoveryCodes, { props: { codes } });

    await expect(wrapper.find('[data-test="copy-codes"]').trigger('click')).resolves.not.toThrow();
    expect(wrapper.text()).toContain('aaaa-bbbb');
});

it('downloads the codes as recovery-codes.txt', async () => {
    URL.createObjectURL = vi.fn(() => 'blob:codes');
    URL.revokeObjectURL = vi.fn();
    let downloaded = '';
    let attached = false;
    vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(function (this: HTMLAnchorElement) {
        downloaded = this.download;
        attached = this.isConnected;
    });

    const wrapper = mount(RecoveryCodes, { props: { codes } });
    await wrapper.find('[data-test="download-codes"]').trigger('click');

    expect(downloaded).toBe('recovery-codes.txt');
    expect(attached).toBe(true);
    expect(document.querySelector('a[download]')).toBeNull();
    expect(URL.revokeObjectURL).toHaveBeenCalledWith('blob:codes');
});

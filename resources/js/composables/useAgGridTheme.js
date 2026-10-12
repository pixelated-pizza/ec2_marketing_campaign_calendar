import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import {
    ModuleRegistry,
    AllCommunityModule,
    themeQuartz,
    colorSchemeDark,
    colorSchemeLight,
} from "ag-grid-community";

// Registering the same modules more than once is harmless
ModuleRegistry.registerModules([AllCommunityModule]);

/**
 * Shared AG Grid theme that follows the app's dark mode (`app-dark` class on <html>).
 *
 *   const { isDark, gridTheme } = useAgGridTheme();
 *   <div :data-ag-theme-mode="isDark ? 'dark' : 'light'"> <AgGridVue :theme="gridTheme" .../> </div>
 */
export function useAgGridTheme(paramOverrides = {}) {
    const isDark = ref(document.documentElement.classList.contains("app-dark"));
    let observer = null;

    onMounted(() => {
        observer = new MutationObserver(() => {
            isDark.value = document.documentElement.classList.contains("app-dark");
        });
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ["class"] });
    });

    onBeforeUnmount(() => observer?.disconnect());

    const gridTheme = computed(() =>
        themeQuartz.withPart(isDark.value ? colorSchemeDark : colorSchemeLight).withParams({
            fontFamily: "Arial, sans-serif",
            fontSize: 13,
            spacing: 6,
            headerFontSize: 13,
            headerFontWeight: 600,
            wrapperBorder: false,
            borderColor: { ref: "foregroundColor", mix: 0.22 },
            rowBorder: true,
            columnBorder: true,
            headerColumnBorder: true,
            headerRowBorder: true,
            ...paramOverrides,
        }),
    );

    return { isDark, gridTheme };
}

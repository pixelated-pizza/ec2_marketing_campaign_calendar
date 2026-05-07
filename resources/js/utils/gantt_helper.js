import { gantt } from "dhtmlx-gantt";

export function renderGantt(container, data, config = {}) {
  gantt.clearAll();
  gantt.config = { ...gantt.config, ...config }; 
  gantt.init(container);     
  gantt.parse(data);         
}
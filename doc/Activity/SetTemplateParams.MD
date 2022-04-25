# SetTemplateParams Activity

Key: `set-template-param`
Type: automatic, no user interaction required

Example:

	<bpmn:task id="EditTemplate" name="Edit template">
    	<bpmn:extensionElements>
			<wf:type>set_template_param</wf:type>
		</bpmn:extensionElements>
		<bpmn:property name="title" default="DummyPage" validation="required,existing-title"/>
		<bpmn:property name="user" default="WikiSysop" validation="existing-user"/>
		<!-- Index of the template on page (starting from 0) -->
		<bpmn:property name="template-index" default="2"/>
		<!-- Param to modify. Can be a string in case of named params, or a number, in case of non-named params (starting from 1, accessor for the param) -->
		<bpmn:property name="template-param" default="1"/>
		<!-- New value to set -->
		<bpmn:property name="value" default="Super edited param"/>
		<bpmn:property name="minor" default="0"/>

		<!-- Output properties -->
		<bpmn:property name="revisionId"/>
		<bpmn:property name="timestamp"/>
      <bpmn:incoming>Flow_1qh6vpl</bpmn:incoming>
      <bpmn:outgoing>Flow_0if1kyp</bpmn:outgoing>

    </bpmn:task>


## Description
This activity allows automatic setting of template params on a page.

Templates are accessed by the order of appearance on the page, starting at 0.
Non-existing templates are counted as links, so they do not count in this list.

Params inside the template can be specified by their name, or the index in case of non-named params.
Index for non-named params starts at 1 (not 0!), as to be consistent with how template params are accessed in the template
itself.

Nested templates are not supported as of now!

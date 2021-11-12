We are using a custom XML schema to add additional info to BPMN diagrams

This schema needs to be included to all the diagrams in order to be usable:
- Publicly available schema file (for development) http://buildservice.bluespice.com/wf/wfSchema.xsd

To use it, add following namespace to definition tag

	 xmlns:wf="http://hallowelt.com/schema/bpmn/wf"

If needed schema location can also be added pointing to the buildservice location above

# Notes about syntax in BPMN diagrams

In `bpmn:task` elements, if you use `bpmn:property` items, you can specify `default` attribtue to assign default property value.

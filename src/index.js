import { addFilter } from "@wordpress/hooks";
import { createHigherOrderComponent } from "@wordpress/compose";
import { BlockControls } from "@wordpress/block-editor";
import {
	Dropdown,
	ToolbarGroup,
	TextControl,
	Button,
	CheckboxControl,
	Flex,
	FlexItem,
} from "@wordpress/components";
import { __ } from "@wordpress/i18n";

addFilter(
	"blocks.registerBlockType",
	"wpdev/accessibility-attributes",
	(settings, name) => {
		if (!settings) return settings;

		return {
			...settings,
			attributes: {
				...settings.attributes,
				ariaLabel: { type: "string", default: "" },
				enableCustomAttributes: { type: "boolean", default: false },
				customAttributes: { type: "string", default: "" },
			},
		};
	}
);

addFilter(
	"editor.BlockEdit",
	"wpdev/accessibility-attributes",
	createHigherOrderComponent((BlockEdit) => {
		return (props) => {
			const {
				name,
				isSelected,
				attributes: {
					ariaLabel = "",
					enableCustomAttributes = false,
					customAttributes = "",
				} = {},
				setAttributes,
			} = props;

			const setAria = (value) => setAttributes({ ariaLabel: value });
			const setEnableCustom = (value) =>
				setAttributes({ enableCustomAttributes: value });
			const setCustom = (value) => setAttributes({ customAttributes: value });

			return (
				<>
					{isSelected && (
						<BlockControls>
							<ToolbarGroup>
								<Dropdown
									className="wpdev-a11y-dropdown"
									popoverProps={{ placement: "bottom-start" }}
									renderToggle={({ isOpen, onToggle }) => (
										<Button
											onClick={onToggle}
											aria-expanded={isOpen}
											aria-haspopup="true"
											label={__("Accessibility", "accessibility-attributes")}
											icon="universal-access-alt"
										/>
									)}
									renderContent={() => (
										<div style={{ padding: 12, minWidth: 280 }}>
											<Flex align="flex-start" gap={8} style={{ marginBottom: 8 }}>
												<FlexItem>
													<strong>
														{__("Accessibility", "accessibility-attributes")}
													</strong>
												</FlexItem>
											</Flex>

											<TextControl
												label={__("Aria label", "accessibility-attributes")}
												value={ariaLabel}
												onChange={setAria}
												help={__(
													"Describes the block's purpose for screen readers.",
													"accessibility-attributes"
												)}
												placeholder={__("e.g. Learn more about…", "accessibility-attributes")}
											/>

											<div style={{ marginTop: 12 }}>
												<CheckboxControl
													label={__("Add custom attributes", "accessibility-attributes")}
													checked={!!enableCustomAttributes}
													onChange={setEnableCustom}
												/>
												{enableCustomAttributes && (
													<TextControl
														label={__("Custom attributes", "accessibility-attributes")}
														value={customAttributes}
														onChange={setCustom}
														placeholder={__("e.g. role|img,aria-describedby|pId", "accessibility-attributes")}
														help={__(
															"Use comma-separated name|value pairs."
														)}
													/>
												)}
											</div>
										</div>
									)}
								/>
							</ToolbarGroup>
						</BlockControls>
					)}
					<BlockEdit {...props} />
				</>
			);
		};
	})
);
